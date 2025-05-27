let newsData = [];

async function fetchNewsData() {
    try {
        console.log('Fetching news data from /php/fetch_news.php...');
        const response = await fetch('/php/fetch_news.php');
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
        }
        newsData = await response.json();
        console.log('Fetched news data:', newsData);
        if (newsData.error) {
            throw new Error(newsData.error);
        }
    } catch (error) {
        console.error('Error fetching news data:', error);
        alert(`ニュースデータの取得に失敗しました。詳細: ${error.message || 'Unknown error'}`);
        newsData = [];
    }
}

function getShortSummary(summary) {
    if (!summary) return "";
    const words = summary.split(/\s+/);
    const shortSummary = words.slice(0, 50).join(" ");
    return shortSummary + (words.length > 50 ? "..." : "");
}

function renderIndexNews() {
    const newsList = document.querySelector(".list ul");
    if (!newsList) return;
    newsList.innerHTML = "";
    if (!Array.isArray(newsData) || newsData.length === 0) {
        newsList.innerHTML = "<li>ニュースデータがありません。</li>";
        return;
    }
    const sortedNews = [...newsData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    const latestNews = sortedNews.slice(0, 3);
    latestNews.forEach((item) => {
        const index = newsData.indexOf(newsData.find(news => news.title === item.title && news.date === item.date));
        const shortSummary = getShortSummary(item.summary);
        newsList.innerHTML += `
            <li class="item">
                <a href="news.html?id=${index}" title="${item.title}">
                    <ul class="tag flex vcenter">
                        <li class="category">${item.category}</li>
                        <li class="date"><time datetime="${item.date}">${item.date.replace(/-/g, "/")}</time></li>
                    </ul>
                    <dl>
                        <dt class="title"><div class="js-t8 line1">${item.title}</div></dt>
                        <dd class="summary">
                            <div class="pc js-t8 line1">${shortSummary}</div>
                            <div class="sp js-t8 line2">${shortSummary}</div>
                        </dd>
                    </dl>
                </a>
            </li>
        `;
    });
}

function renderNews(filteredData) {
    const newsList = document.getElementById("newsList");
    if (!newsList) return;
    newsList.innerHTML = "";
    console.log('Rendering news with filtered data:', filteredData);
    if (!Array.isArray(filteredData) || filteredData.length === 0) {
        newsList.innerHTML = "<div>ニュースデータがありません。</div>";
        return;
    }
    const sortedData = [...filteredData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    sortedData.forEach((item) => {
        const index = newsData.indexOf(item);
        const shortSummary = getShortSummary(item.summary);
        const hasImage = item.image && typeof item.image === 'string' && item.image.trim() !== '' && item.image.toLowerCase() !== 'null';
        console.log(`Rendering news item: ${item.title || 'Unknown title'}, image: ${item.image || 'No image'}, hasImage: ${hasImage}`);
        const imageHtml = hasImage ? `
            <div class="news-image">
                <img src="${item.image}" alt="${item.title}" onerror="this.parentElement.style.display='none';">
            </div>
        ` : '';
        newsList.innerHTML += `
            <div class="news-item">
                <div class="news-wrapper">
                    ${imageHtml}
                    <div class="news-content">
                        <div class="tag">
                            <span class="date">${item.date}</span>
                            <span class="category">${item.category}</span>
                        </div>
                        <div class="title">${item.title}</div>
                        <div class="summary">${shortSummary} <a href="news.html?id=${index}" class="summary-link">もっと見る。。。</a></div>
                    </div>
                </div>
            </div>
        `;
    });

    adjustImageHeights();
}

function adjustImageHeights() {
    const newsItems = document.querySelectorAll(".news-item");
    newsItems.forEach(item => {
        const newsContent = item.querySelector(".news-content");
        const newsImage = item.querySelector(".news-image img");
        if (newsContent && newsImage) {
            const contentHeight = newsContent.offsetHeight;
            const maxHeight = 300;
            const newHeight = Math.min(contentHeight, maxHeight);
            newsImage.style.height = `${newHeight}px`;
        }
    });
}

function filterNews() {
    const categoryFilter = document.getElementById("categoryFilter");
    const dateFilter = document.getElementById("dateFilter");
    if (!categoryFilter || !dateFilter) return;

    let category = categoryFilter.value;
    let dateOrder = dateFilter.value;
    let filtered = [...newsData];

    if (category !== "all") {
        filtered = filtered.filter(item => item.category === category);
    }

    if (dateOrder === "desc") {
        filtered.sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            console.log(`Sorting: ${a.title || 'Unknown title'} (${a.created_at}) vs ${b.title || 'Unknown title'} (${b.created_at}) -> ${dateB - dateA}`);
            return dateB - dateA;
        });
    } else if (dateOrder === "asc") {
        filtered.sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            console.log(`Sorting: ${a.title || 'Unknown title'} (${a.created_at}) vs ${b.title || 'Unknown title'} (${b.created_at}) -> ${dateA - dateB}`);
            return dateA - dateB;
        });
    }

    renderNews(filtered);
}

document.addEventListener("DOMContentLoaded", async () => {
    console.log("DOM fully loaded, starting news.js execution...");

    await fetchNewsData();

    const urlParams = new URLSearchParams(window.location.search);
    const newsId = urlParams.get("id");
    console.log("newsId from URL:", newsId);

    const filterBar = document.querySelector(".filter-bar");
    if (filterBar) {
        if (newsId !== null) {
            filterBar.style.display = "none";
            console.log("Hiding filter bar because newsId is present");
        } else {
            filterBar.style.display = "flex";
            console.log("Showing filter bar because newsId is not present");
        }
    }

    const introText = document.querySelector(".text");
    if (introText) {
        if (newsId !== null) {
            introText.style.display = "none";
        } else {
            introText.style.display = "block";
        }
    }

    console.log("Rendering index news...");
    renderIndexNews();

    if (document.getElementById("newsList")) {
        console.log("Found newsList element, proceeding...");
        if (newsId !== null) {
            console.log("newsId is present, rendering single news item...");
            const selectedNews = newsData[newsId];
            if (selectedNews) {
                document.title = selectedNews.title + ' - 株式会社アイティーエフ';
                const metaDescription = document.querySelector('meta[name="description"]');
                if (metaDescription) {
                    metaDescription.setAttribute('content', selectedNews.summary.substring(0, 150) + '...');
                }
                const metaOgTitle = document.querySelector('meta[property="og:title"]');
                if (metaOgTitle) {
                    metaOgTitle.setAttribute('content', selectedNews.title + ' - 株式会社アイティーエフ');
                }
                const metaOgDescription = document.querySelector('meta[property="og:description"]');
                if (metaOgDescription) {
                    metaOgDescription.setAttribute('content', selectedNews.summary.substring(0, 150) + '...');
                }
                const metaTwitterTitle = document.querySelector('meta[name="twitter:title"]');
                if (metaTwitterTitle) {
                    metaTwitterTitle.setAttribute('content', selectedNews.title + ' - 株式会社アイティーエフ');
                }
                const metaTwitterDescription = document.querySelector('meta[name="twitter:description"]');
                if (metaTwitterDescription) {
                    metaTwitterDescription.setAttribute('content', selectedNews.summary.substring(0, 150) + '...');
                }

                const hasImage = selectedNews.image && typeof selectedNews.image === 'string' && selectedNews.image.trim() !== '' && selectedNews.image.toLowerCase() !== 'null';
                console.log(`Rendering single news item: ${selectedNews.title || 'Unknown title'}, image: ${selectedNews.image || 'No image'}, hasImage: ${hasImage}`);
                const imageHtml = hasImage ? `
                    <div class="news-image">
                        <img src="${selectedNews.image}" alt="${selectedNews.title}" onerror="this.parentElement.style.display='none';">
                    </div>
                ` : '';
                const newsList = document.getElementById("newsList");
                newsList.innerHTML = `
                    <div class="news-item">
                        <div class="news-wrapper full-view-wrapper">
                            ${imageHtml}
                            <div class="news-content">
                                <div class="tag">
                                    <span class="date">${selectedNews.date}</span>
                                    <span class="category">${selectedNews.category}</span>
                                </div>
                                <div class="title">${selectedNews.title}</div>
                                <div class="summary">${selectedNews.content}</div>
                            </div>
                        </div>
                    </div>
                    <div class="read-more-btn">
                        <a href="news.html" class="read-more">おしらせへ戻る</a>
                    </div>
                `;
            } else {
                const newsList = document.getElementById("newsList");
                newsList.innerHTML = "<div>ニュースが見つかりませんでした。</div>";
            }
        } else {
            console.log("newsId is not present, rendering all news items...");
            const sortedNewsData = Array.isArray(newsData) ? [...newsData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)) : [];
            renderNews(sortedNewsData);

            const categoryFilter = document.getElementById("categoryFilter");
            const dateFilter = document.getElementById("dateFilter");

            if (dateFilter) {
                dateFilter.value = "desc";
                console.log("dateFilter found, setting default to desc and adding event listener...");
                dateFilter.addEventListener("change", filterNews);
            }

            if (categoryFilter) {
                console.log("categoryFilter found, adding event listener...");
                categoryFilter.addEventListener("change", filterNews);
            }
        }
    }

    window.addEventListener("resize", adjustImageHeights);
});