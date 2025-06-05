import requests
from bs4 import BeautifulSoup
import re
import json  # ✅ 新增匯入

def fetch_book_info(url):
    res = requests.get(url, headers={'User-Agent': 'Mozilla/5.0'})
    soup = BeautifulSoup(res.text, 'html.parser')

    # 書名
    title = soup.find('h1').get_text(strip=True) if soup.find('h1') else ''

    # 書封
    cover_tag = soup.select_one('meta[property="og:image"]')
    cover_url = cover_tag['content'] if cover_tag else ''

    # 書籍簡介（抓 <div class="content"> 中多行文字）
    description = ''
    desc_div = soup.find('div', class_='content')
    if desc_div:
        lines = [line.strip() for line in desc_div.stripped_strings]
        description = '\n'.join(lines)

    # 從 <meta name="description"> 抓作者與出版社
    meta_desc = soup.find('meta', {'name': 'description'})
    meta_content = meta_desc['content'] if meta_desc else ''

    # 用正則式抓作者與出版社
    author_match = re.search(r'作者：(.+?)，', meta_content)
    publisher_match = re.search(r'出版社：(.+?)，', meta_content)
    category_match = re.search(r'類別：(.+?)(?:，|$)', meta_content)


    # 處理多作者
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
        'category':category,
        'cover_url': cover_url,
        'description': description
    }

# ✅ 測試爬取一筆書籍資訊，並輸出 JSON 格式
if __name__ == '__main__':
    test_url = 'https://www.books.com.tw/products/0011019533?loc=P_0152__1003'
    data = fetch_book_info(test_url)

    # 輸出為 JSON 格式（含中文）
    print(json.dumps(data, ensure_ascii=False, indent=2))
