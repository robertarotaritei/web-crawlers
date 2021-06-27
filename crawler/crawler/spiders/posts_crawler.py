import scrapy

class PostsSpirder(scrapy.Spider):
    name = "posts"

    start_urls = [
        'https://www.zyte.com/blog/page/1',
    ]

    def parse(self, response):
        if response.status == 200:
            for post in response.css('div.oxy-post'):
                yield {
                    'title': post.css('.oxy-post-title::text')[0].get(),
                    'author': post.css('.oxy-post-meta div::text')[0].get().strip('\n\t'),
                    'length': "%s minutes" % post.css('.oxy-post-meta span::text')[1].get()
                }
            url = response.url.rsplit('/', 2)
            current_page = url[0]
            if 'page' not in current_page:
                current_page += '/blog/page'
                page_number = 2
            else:
                page_number = int(url[1]) + 1
            next_page = "%s/%s" % (current_page, page_number)
            yield scrapy.Request(next_page, callback=self.parse)