import requests
from bs4 import BeautifulSoup
import json
import pymysql

def fetch_book_info(url):
    headers = {'User-Agent': 'Mozilla/5.0'}
    res = requests.get(url, headers=headers)
    soup = BeautifulSoup(res.text, 'html.parser')
    
    ld_json_tag = soup.find('script', type='application/ld+json')
    if not ld_json_tag:
        print("❌ 找不到 JSON-LD 結構化資料")
        return None
    
    data = json.loads(ld_json_tag.string)
    
    title = data.get('name', '')
    
    authors = []
    author_data = data.get('author')
    if isinstance(author_data, list):
        authors = [a.get('name', '') for a in author_data if 'name' in a]
    elif isinstance(author_data, dict):
        authors = [author_data.get('name', '')]
    
    publisher = ''
    publisher_data = data.get('publisher')
    if isinstance(publisher_data, list) and len(publisher_data) > 0:
        publisher = publisher_data[0].get('name', '')
    elif isinstance(publisher_data, dict):
        publisher = publisher_data.get('name', '')
    
    category = data.get('category', '')
    cover_url = data.get('image', '')
    description = data.get('description', '')
    
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
        cursor.execute("SELECT book_id FROM book WHERE title=%s", (book['title'],))
        result = cursor.fetchone()
        if result:
            print(f"⚠️ 書籍《{book['title']}》已存在，跳過。")
            return
        
        sql_book = "INSERT INTO book (title, publisher, category, cover_url, description) VALUES (%s, %s, %s, %s, %s)"
        cursor.execute(sql_book, (book['title'], book['publisher'], book['category'], book['cover_url'], book['description']))
        book_id = cursor.lastrowid
        
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
        print(f"✅ 成功匯入書籍《{book['title']}》")

if __name__ == '__main__':
    db_config = {
        'host': '127.0.0.1',
        'port': 3306,
        'user': 'admin',
        'password': 'Admin1234!',
        'database': 'book_system',
        'charset': 'utf8mb4'
    }
    
    book_urls = [
        'https://www.books.com.tw/products/0011021052',
        'https://www.books.com.tw/products/0011002289',
        'https://www.books.com.tw/products/0010870942'
        # 你可以加更多書籍網址
    ]
    
    conn = pymysql.connect(autocommit=False, **db_config)
    
    for url in book_urls:
        try:
            print(f"📖 開始爬取：{url}")
            book_data = fetch_book_info(url)
            if book_data and book_data['title']:
                print("抓取結果：")
                print(f"書名：{book_data['title']}")
                print(f"作者：{book_data['authors']}")
                print(f"出版社：{book_data['publisher']}")
                print(f"分類：{book_data['category']}")
                print(f"封面：{book_data['cover_url']}")
                print(f"簡介：{book_data['description']}")
                save_book_to_db(conn, book_data)
            else:
                print("❌ 抓取失敗：沒有書名或找不到資料。")
        except Exception as e:
            print(f"❌ 錯誤：{e}")
    
    conn.close()
