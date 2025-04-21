// Function to extract first 50 words from the summary
function getShortSummary(summary) {
    const words = summary.split(/\s+/); // Split by whitespace
    const shortSummary = words.slice(0, 50).join(" "); // Take first 50 words
    return shortSummary + (words.length > 50 ? "..." : ""); // Add ellipsis if summary is longer
}

function renderNews(newsData) {
    const newsList = document.getElementById("newsList");
    if (!newsList) return;
    newsList.innerHTML = "";
    // Sort the news by date (most recent first) before rendering
    const sortedData = [...newsData].sort((a, b) => new Date(b.date) - new Date(a.date));
    sortedData.forEach((item, index) => { // Include index here
        const shortSummary = getShortSummary(item.summary); // Use first 50 words of summary
        newsList.innerHTML += `
            <div class="news-item">
                <div class="news-wrapper">
                    <div class="news-image">
                        <img src="${item.image}" alt="${item.title}">
                    </div>
                    <div class="news-content">
                        <div class="tag">
                            <span class="date">${item.date}</span>
                            <span class="category">${item.category}</span>
                            <span class="posted-by">Posted By: ${item.postedBy}</span>
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

function filterNews(newsData) {
    const categoryFilter = document.getElementById("categoryFilter");
    const dateFilter = document.getElementById("dateFilter");
    const postedByFilter = document.getElementById("postedByFilter");
    if (!categoryFilter || !dateFilter || !postedByFilter) return;

    let category = categoryFilter.value;
    let dateOrder = dateFilter.value;
    let postedBy = postedByFilter.value;
    let filtered = [...newsData];

    if (category !== "all") {
        filtered = filtered.filter(item => item.category === category);
    }

    if (postedBy !== "all") {
        filtered = filtered.filter(item => item.postedBy === postedBy);
    }

    if (dateOrder === "desc") {
        filtered.sort((a, b) => new Date(b.date) - new Date(a.date));
    } else if (dateOrder === "asc") {
        filtered.sort((a, b) => new Date(a.date) - new Date(b.date));
    }

    renderNews(filtered);
}

// Function to adjust image heights based on news content height
function adjustImageHeights() {
    const newsItems = document.querySelectorAll(".news-item");
    newsItems.forEach(item => {
        const newsContent = item.querySelector(".news-content");
        const newsImage = item.querySelector(".news-image img"); // Now targeting the <img> tag
        if (newsContent && newsImage) {
            const contentHeight = newsContent.offsetHeight;
            const maxHeight = 300; // Max height as defined in CSS
            const newHeight = Math.min(contentHeight, maxHeight);
            newsImage.style.height = `${newHeight}px`;
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const newsId = urlParams.get("id");

    const filterBar = document.querySelector(".filter-bar");
    const introText = document.querySelector(".text");
    const newsListContainer = document.getElementById("newsList");
    const singleNewsContainer = document.querySelector(".single-news-container"); // You might need to adjust this selector

    fetch('php/get_news.php')
        .then(response => response.json())
        .then(newsData => {
            if (filterBar) {
                filterBar.style.display = newsId !== null ? "none" : "flex";
                document.getElementById("categoryFilter").addEventListener("change", () => filterNews(newsData));
                document.getElementById("dateFilter").addEventListener("change", () => filterNews(newsData));
                document.getElementById("postedByFilter").addEventListener("change", () => filterNews(newsData));
            }

            if (introText) {
                introText.style.display = newsId !== null ? "none" : "block";
            }

            if (newsListContainer) {
                if (newsId !== null && newsData[newsId]) {
                    const selectedNews = newsData[newsId];
                    newsListContainer.innerHTML = `
                        <div class="news-item">
                            <div class="news-wrapper full-view-wrapper">
                                <div class="news-image">
                                    <img src="${selectedNews.image}" alt="${selectedNews.title}">
                                </div>
                                <div class="news-content">
                                    <div class="tag">
                                        <span class="date">${selectedNews.date}</span>
                                        <span class="category">${selectedNews.category}</span>
                                        <span class="posted-by">Posted By: ${selectedNews.postedBy}</span>
                                    </div>
                                    <div class="title">${selectedNews.title}</div>
                                    <div class="summary">${selectedNews.summary}</div>
                                </div>
                            </div>
                        </div>
                        <div class="read-more-btn">
                            <a href="news.html" class="read-more">おしらせへ戻る</a>
                        </div>
                    `;
                } else {
                    renderNews(newsData);
                }
            }
        })
        .catch(error => {
            console.error("Error fetching news data:", error);
            const newsList = document.getElementById("newsList");
            if (newsList) {
                newsList.innerHTML = "<p>ニュースの読み込みに失敗しました。</p>";
            }
        });
    window.addEventListener("resize", adjustImageHeights);
});