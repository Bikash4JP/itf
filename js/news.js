let newsData = []; // Initialize as empty; will be populated from fetch

// Function to fetch news data from the server
async function fetchNewsData() {
    try {
        console.log('Fetching news data from /php/fetch_news.php...');
        const response = await fetch('/php/fetch_news.php'); // Updated path
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
        }
        newsData = await response.json();
        console.log('Fetched news data:', newsData); // Log the fetched data
        if (newsData.error) {
            throw new Error(newsData.error);
        }
    } catch (error) {
        console.error('Error fetching news data:', error);
        alert('ニュースデータの取得に失敗しました。詳細: ' + error.message);
        newsData = []; // Ensure newsData is an empty array on error
    }
}

// Function to extract first 50 words from the summary
function getShortSummary(summary) {
    if (!summary) return "";
    const words = summary.split(/\s+/); // Split by whitespace
    const shortSummary = words.slice(0, 50).join(" "); // Take first 50 words
    return shortSummary + (words.length > 50 ? "..." : ""); // Add ellipsis if summary is longer
}

function renderIndexNews() {
    const newsList = document.querySelector(".list ul");
    if (!newsList) return;
    newsList.innerHTML = "";
    // Check if newsData is an array and not empty
    if (!Array.isArray(newsData) || newsData.length === 0) {
        newsList.innerHTML = "<li>ニュースデータがありません。</li>";
        return;
    }
    // Sort to ensure latest-to-oldest order
    const sortedNews = [...newsData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    const latestNews = sortedNews.slice(0, 3); // Take the first 3 items
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
    // Check if filteredData is an array and not empty
    if (!Array.isArray(filteredData) || filteredData.length === 0) {
        newsList.innerHTML = "<div>ニュースデータがありません。</div>";
        return;
    }
    // Sort to ensure latest-to-oldest order
    const sortedData = [...filteredData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    sortedData.forEach((item) => {
        const index = newsData.indexOf(item);
        const shortSummary = getShortSummary(item.summary);
        newsList.innerHTML += `
            <div class="news-item">
                <div class="news-wrapper">
                    <div class="news-image">
                        <img src="${item.image || 'images/default-news.jpg'}" alt="${item.title}">
                    </div>
                    <div class="news-content">
                        <div class="tag">
                            <span class="date">${item.date}</span>
                            <span class="category">${item.category}</span>
                            <span class="posted-by">Posted By: ${item.posted_by}</span>
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
            console.log(`Sorting: ${a.title} (${a.created_at}) vs ${b.title} (${b.created_at}) -> ${dateB - dateA}`);
            return dateB - dateA;
        });
    } else if (dateOrder === "asc") {
        filtered.sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            console.log(`Sorting: ${a.title} (${a.created_at}) vs ${b.title} (${b.created_at}) -> ${dateA - dateB}`);
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
    } else {
        console.warn("filter-bar element not found");
    }

    const introText = document.querySelector(".text");
    if (introText) {
        if (newsId !== null) {
            introText.style.display = "none";
        } else {
            introText.style.display = "block";
        }
    }

    if (document.querySelector(".list ul")) {
        console.log("Rendering index news...");
        renderIndexNews();
    } else if (document.getElementById("newsList")) {
        console.log("Found newsList element, proceeding...");
        if (newsId !== null) {
            console.log("newsId is present, rendering single news item...");
            const selectedNews = newsData[newsId];
            if (selectedNews) {
                const newsList = document.getElementById("newsList");
                newsList.innerHTML = `
                    <div class="news-item">
                        <div class="news-wrapper full-view-wrapper">
                            <div class="news-image">
                                <img src="${selectedNews.image || 'images/default-news.jpg'}" alt="${selectedNews.title}">
                            </div>
                            <div class="news-content">
                                <div class="tag">
                                    <span class="date">${selectedNews.date}</span>
                                    <span class="category">${selectedNews.category}</span>
                                    <span class="posted-by">Posted By: ${selectedNews.posted_by}</span>
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
            // Ensure default view is sorted latest-to-oldest
            const sortedNewsData = Array.isArray(newsData) ? [...newsData].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)) : [];
            renderNews(sortedNewsData);

            const categoryFilter = document.getElementById("categoryFilter");
            const dateFilter = document.getElementById("dateFilter");

            if (dateFilter) {
                // Set default value to "desc" (latest to oldest)
                dateFilter.value = "desc";
                console.log("dateFilter found, setting default to desc and adding event listener...");
                dateFilter.addEventListener("change", filterNews);
            } else {
                console.warn("dateFilter element not found");
            }

            if (categoryFilter) {
                console.log("categoryFilter found, adding event listener...");
                categoryFilter.addEventListener("change", filterNews);
            } else {
                console.warn("categoryFilter element not found");
            }
        }
    } else {
        console.warn("newsList element not found");
    }

    window.addEventListener("resize", adjustImageHeights);
});