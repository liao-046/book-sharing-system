import requests
from bs4 import BeautifulSoup
import re
import pymysql

def fetch_book_info(url):
    res = requests.get(url, headers={'User-Agent': 'Mozilla/5.0'})
    soup = BeautifulSoup(res.text, 'html.parser')

    title = soup.find('h1').get_text(strip=True) if soup.find('h1') else ''
    cover_tag = soup.select_one('meta[property="og:image"]')
    cover_url = cover_tag['content'] if cover_tag else ''

    description = ''
    desc_div = soup.find('div', class_='content')
    if desc_div:
        lines = [line.strip() for line in desc_div.stripped_strings]
        description = '\n'.join(lines)

    meta_desc = soup.find('meta', {'name': 'description'})
    meta_content = meta_desc['content'] if meta_desc else ''

    author_match = re.search(r'作者：(.+?)，', meta_content)
    publisher_match = re.search(r'出版社：(.+?)，', meta_content)
    category_match = re.search(r'類別：(.+?)(?:，|$)', meta_content)

    authors = []
    if author_match:
        raw_authors = author_match.group(1)
        authors = [a.strip() for a in re.split(r'[ ,，]', raw_authors) if a.strip()]

    publisher = publisher_match.group(1).strip() if publisher_match else ''
    category = category_match.group(1).strip() if category_match else ''

    return {
        'title': title,
        'authors': authors,
        'publisher': publisher,
        'category': category,
        'cover_url': cover_url,
        'description': description
    }

def save_book_to_db(conn, book):
    with conn.cursor() as cursor:
        # 檢查書是否存在
        cursor.execute("SELECT book_id FROM book WHERE title=%s", (book['title'],))
        result = cursor.fetchone()
        if result:
            print(f"書籍《{book['title']}》已存在，跳過。")
            return

        # 插入書籍
        sql_book = "INSERT INTO book (title, publisher, category, cover_url, description) VALUES (%s, %s, %s, %s, %s)"
        cursor.execute(sql_book, (book['title'], book['publisher'], book['category'], book['cover_url'], book['description']))
        book_id = cursor.lastrowid

        # 插入作者及關聯
        for author_name in book['authors']:
            cursor.execute("SELECT author_id FROM author WHERE name=%s", (author_name,))
            author_result = cursor.fetchone()
            if author_result:
                author_id = author_result[0]
            else:
                cursor.execute("INSERT INTO author (name) VALUES (%s)", (author_name,))
                author_id = cursor.lastrowid

            cursor.execute("INSERT INTO book_author (book_id, author_id) VALUES (%s, %s)", (book_id, author_id))

        conn.commit()
        print(f"成功匯入書籍《{book['title']}》")

if __name__ == '__main__':
    # 你的資料庫連線設定，請改成你自己的帳號密碼跟資料庫名稱
    db_config = {
        'host': '140.122.184.128',
        'port': 3306,
        'user': 'team19',
        'password': 'Wy$Kq83Nbm',
        'database': 'team19',
        'charset': 'utf8mb4'
    }

    # 一次爬多本書的網址清單
    book_urls = [
        'https://www.kingstone.com.tw/basic/2018612493002/?lid=acg_index_gift',
        'https://www.kingstone.com.tw/basic/2019461601266/?lid=acg_index_gift',
        # 你可以繼續加更多網址
    ]

    conn = pymysql.connect(autocommit=False, **db_config)

    for url in book_urls:
        try:
            print(f"開始爬取：{url}")
            book_data = fetch_book_info(url)
            if book_data['title']:  # 有抓到書名才存
                save_book_to_db(conn, book_data)
            else:
                print("抓取失敗，沒有書名。")
        except Exception as e:
            print(f"爬取或存入資料錯誤: {e}")

    conn.close()
