import requests
from bs4 import BeautifulSoup
import re
import json
import pymysql

def fetch_book_info(url):
    headers = {'User-Agent': 'Mozilla/5.0'}
    res = requests.get(url, headers=headers)
    soup = BeautifulSoup(res.text, 'html.parser')

    # 書名
    title_tag = soup.find('h1')
    title = title_tag.get_text(strip=True) if title_tag else ''

    # 書封
    cover_tag = soup.select_one('meta[property="og:image"]')
    cover_url = cover_tag['content'] if cover_tag else ''

    # 書籍簡介
    description = ''
    desc_div = soup.find('div', class_='content')
    if desc_div:
        lines = [line.strip() for line in desc_div.stripped_strings]
        description = '\n'.join(lines)

    # 從 meta 抓作者、出版社與類別
    meta_desc = soup.find('meta', {'name': 'description'})
    meta_content = meta_desc['content'] if meta_desc else ''

    author_match = re.search(r'作者：(.+?)，', meta_content)
    publisher_match = re.search(r'出版社：(.+?)，', meta_content)
    category_match = re.search(r'類別：(.+?)(?:，|$)', meta_content)

    # 多作者處理
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
        # 檢查是否已存在
        cursor.execute("SELECT book_id FROM book WHERE title=%s", (book['title'],))
        result = cursor.fetchone()
        if result:
            print(f"⚠️ 書籍《{book['title']}》已存在，跳過。")
            return

        # 插入書籍
        sql_book = "INSERT INTO book (title, publisher, category, cover_url, description) VALUES (%s, %s, %s, %s, %s)"
        cursor.execute(sql_book, (book['title'], book['publisher'], book['category'], book['cover_url'], book['description']))
        book_id = cursor.lastrowid

        # 插入作者 + 關聯表
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
        print(f"✅ 已匯入《{book['title']}》至資料庫。")

if __name__ == '__main__':
    # 資料庫連線設定
    db_config = {
        'host': '127.0.0.1',
        'port': 3306,
        'user': 'admin',
        'password': 'Admin1234!',
        'database': 'book_system',
        'charset': 'utf8mb4'
    }

    print("📥 請貼上多個博客來網址（每行一個），輸入空行結束：")
    urls = []
    while True:
        line = input()
        if line.strip() == '':
            break
        urls.append(line.strip())

    # 建立資料庫連線
    conn = pymysql.connect(autocommit=False, **db_config)

    for idx, url in enumerate(urls, start=1):
        print(f"\n🔎 [{idx}] 抓取：{url}")
        try:
            book = fetch_book_info(url)
            if book['title']:
                print("📘 抓取成功：")
                print(json.dumps(book, ensure_ascii=False, indent=2))
                save_book_to_db(conn, book)
            else:
                print("⚠️ 抓不到書名，跳過。")
        except Exception as e:
            print(f"❌ 發生錯誤：{e}")

    conn.close()
    print("\n✅ 全部處理完成！")
