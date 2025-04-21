const newsData = [
    {
        category: "入社情報",
        date: "2025-04-01",
        title: "2025年4月1日より新しいスタッフが東京オフィスに加わりました。",
        summary: "インドネシア出身の皆様との円滑なコミュニケーションを目的に、インドネシア担当コーディネーターとしてMADE（マデ）さんが入社しました。インドネシア出身の皆様との円滑なコミュニケーションを目的に、インドネシア担当コーディネーターとしてMADE（マデ）さんが入社しました。インドネシア出身の皆様との円滑なコミュニケーションを目的に、インドネシア担当コーディネーターとしてMADE（マデ）さんが入社しました。",
        image: "images/news1.jpg",
        postedBy: "ITF Admin"
    },
    {
        category: "連携",
        date: "2025-04-01",
        title: "近隣パートナー企業への新メンバーご紹介",
        summary: "2025年4月上旬、ITFでは新たに入社したスタッフをご紹介するため、東京近郊の提携パートナー企業様に向けて...東京近郊の提携パートナー企業様に向けて...東京近郊の提携パートナー企業様に向けて...",
        image: "images/news2.jpg",
        postedBy: "HR Team"
    },
    {
        category: "募集",
        date: "2025-04-04",
        title: "『緑樹会』介護施設にて正社員募集を開始しました",
        summary: "ITFの提携先である介護施設『緑樹会（りょくじゅかい）』様にて、介護職の正社員募集がスタートしました。ITFの提携先である介護施設『緑樹会（りょくじゅかい）』様にて、介護職の正社員募集がスタートしました。ITFの提携先である介護施設『緑樹会（りょくじゅかい）』様にて、介護職の正社員募集がスタートしました。",
        image: "images/news3.jpg",
        postedBy: "ITF Admin"
    },
    {
        category: "イベント",
        date: "2025-04-12",
        title: "外国人材向けキャリアフェア2025を開催",
        summary: "ITF主催のキャリアフェアが大阪で開催されます。ベトナム、インドネシア、フィリピンからの人材と日本企業をつなぐイベントです。ベトナム、インドネシア、フィリピンからの人材と日本企業をつなぐイベントです。ベトナム、インドネシア、フィリピンからの人材と日本企業をつなぐイベントです。",
        image: "images/news4.jpg",
        postedBy: "HR Team"
    },
    {
        category: "連携",
        date: "2025-04-15",
        title: "タイ企業との新たなパートナーシップ締結",
        summary: "ITFはタイのトップ人材紹介会社と提携し、タイ人エンジニアの日本企業への紹介を強化します。タイ人エンジニアの日本企業への紹介を強化します。タイ人エンジニアの日本企業への紹介を強化します。",
        image: "images/news5.jpg",
        postedBy: "ITF Admin"
    },
    {
        category: "募集",
        date: "2025-04-18",
        title: "東京オフィスで通訳スタッフ募集開始",
        summary: "英語と日本語のバイリンガル通訳者を募集開始。外国人材と企業のスムーズなコミュニケーションをサポートします。外国人材と企業のスムーズなコミュニケーションをサポートします。外国人材と企業のスムーズなコミュニケーションをサポートします。Lorem ipsum dolor sit amet consectetur adipisicing elit. Magni nesciunt nisi sunt doloremque libero. Nostrum sequi fuga non obcaecati mollitia, quasi dolor enim sit et atque molestias. Mollitia neque incidunt autem nemo, amet nisi impedit soluta accusamus dicta ab maiores delectus assumenda, reiciendis omnis consequatur ratione laudantium voluptate, quisquam ullam nostrum pariatur reprehenderit. Consequuntur perspiciatis, quibusdam facere vel, nisi libero facilis ducimus tenetur, aut dicta officiis maxime tempora ad repellat eum quod vitae ut! Consectetur maiores ad reprehenderit nemo. Ullam nobis repellendus officia aut repudiandae esse accusamus amet nemo. Cupiditate ex soluta et error repellat! Doloribus sapiente nulla distinctio deserunt totam et maiores consequuntur consectetur culpa eum repellendus est libero nisi inventore a dolorem eaque rem, iusto aliquam architecto. Atque magnam perferendis labore, quisquam consectetur molestias est distinctio corporis officia enim dolorum eius magni, optio quis dolore aspernatur facilis adipisci praesentium. Facere vero, nam quis iusto ab enim quidem id sit! Quae quia qui amet ratione deserunt magnam? Eligendi ratione, eum quae, rem unde exercitationem, in itaque delectus voluptas vel dolorem quisquam. Consequatur rem quidem mollitia accusamus hic aperiam maxime impedit temporibus iusto fugiat cumque expedita nesciunt odio possimus recusandae id eligendi suscipit nulla soluta, illum nobis dolores illo aliquid! Deserunt, voluptatem. Quis dolorum cupiditate delectus odio? Eligendi at hic reprehenderit modi est et illum nihil animi, deleniti adipisci maxime natus optio eos quasi inventore? Id pariatur ducimus aliquam perferendis itaque enim veniam totam reprehenderit obcaecati laboriosam, impedit atque debitis eum officiis asperiores saepe, corporis fugit possimus deserunt eos eligendi dolor optio. Quia velit consequuntur consectetur odit sunt quo non aliquid? Dicta, inventore unde perferendis ullam molestias molestiae, cupiditate non incidunt eveniet voluptatem deserunt, velit consequuntur necessitatibus aliquid ipsa laudantium pariatur sed iste quia quos? Laboriosam alias vel, excepturi eaque laborum sapiente. Optio aliquam deserunt deleniti, vel sunt molestiae laudantium!",
        image: "images/cat.jpg",
        postedBy: "HR Team"
    }
];

// Function to extract first 50 words from the summary
function getShortSummary(summary) {
    const words = summary.split(/\s+/); // Split by whitespace
    const shortSummary = words.slice(0, 50).join(" "); // Take first 50 words
    return shortSummary + (words.length > 50 ? "..." : ""); // Add ellipsis if summary is longer
}

// Remove duplicate renderIndexNews function and keep the correct one
function renderIndexNews() {
    const newsList = document.querySelector(".list ul");
    if (!newsList) return;
    newsList.innerHTML = "";
    // Create a copy of newsData to avoid modifying the original array
    const sortedNews = [...newsData].sort((a, b) => new Date(b.date) - new Date(a.date));
    const latestNews = sortedNews.slice(0, 3);
    latestNews.forEach((item) => {
        // Use the original newsData to find the index
        const index = newsData.indexOf(newsData.find(news => news.title === item.title && news.date === item.date));
        const shortSummary = getShortSummary(item.summary); // Use first 50 words of summary
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
    // Sort the news by date (most recent first) before rendering
    const sortedData = [...filteredData].sort((a, b) => new Date(b.date) - new Date(a.date));
    sortedData.forEach((item) => {
        const index = newsData.indexOf(item);
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

    // Adjust image heights after rendering
    adjustImageHeights();
}

// Function to adjust image heights based on news content height
function adjustImageHeights() {
    const newsItems = document.querySelectorAll(".news-item");
    newsItems.forEach(item => {
        const newsContent = item.querySelector(".news-content");
        const newsImage = item.querySelector(".news-image img"); // Now targeting the <img> tag
        if (newsContent && newsImage) {
            const contentHeight = newsContent.offsetHeight;
            // Set image height to match content height, but not more than 300px
            const maxHeight = 300; // Max height as defined in CSS
            const newHeight = Math.min(contentHeight, maxHeight);
            newsImage.style.height = `${newHeight}px`;
        }
    });
}

function filterNews() {
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
        filtered.sort((a, b) => new Date(a.date) - new Date(a.date));
    }

    renderNews(filtered);
}

document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const newsId = urlParams.get("id");

    // Filter bar ko hide karne ke liye
    const filterBar = document.querySelector(".filter-bar");
    if (filterBar) {
        if (newsId !== null) {
            filterBar.style.display = "none"; // Full news view mein filter bar hide
        } else {
            filterBar.style.display = "flex"; // Default news list mein show
        }
    }

    // "ITFからの最新ニュースやお知らせをこちらに掲載します。" line ko hide karne ke liye
    const introText = document.querySelector(".text");
    if (introText) {
        if (newsId !== null) {
            introText.style.display = "none"; // Full news view mein text hide
        } else {
            introText.style.display = "block"; // Default news list mein show
        }
    }

    if (document.querySelector(".list ul")) {
        renderIndexNews();
    } else if (document.getElementById("newsList")) {
        if (newsId !== null) {
            const selectedNews = newsData[newsId];
            if (selectedNews) {
                const newsList = document.getElementById("newsList");
                newsList.innerHTML = `
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
            }
        } else {
            renderNews(newsData);
            document.getElementById("categoryFilter").addEventListener("change", filterNews);
            document.getElementById("dateFilter").addEventListener("change", filterNews);
            document.getElementById("postedByFilter").addEventListener("change", filterNews);
        }
    }

    // Adjust image heights on window resize
    window.addEventListener("resize", adjustImageHeights);
});
// Function to show login pop-up
// Function to show login pop-up
function showLoginPopup() {
    const loginPopup = document.getElementById("loginPopup");
    if (loginPopup) {
        loginPopup.style.display = "flex";
        document.body.classList.add("blur");

        // Handle form submission via AJAX
        const loginForm = document.getElementById("loginForm");
        loginForm.addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(loginForm);
            fetch("php/login.php", {
                method: "POST",
                body: formData
            })
            .then(response => {
                console.log("Raw response:", response); // Debug log
                return response.json();
            })
            .then(data => {
                console.log("Parsed JSON data:", data); // Debug log
                if (data.success) {
                    console.log("Redirecting to:", data.redirect); // Debug log
                    // If login is successful, redirect to the URL
                    window.location.href = data.redirect;
                } else {
                    // If login fails, show error message in pop-up
                    const loginMessage = document.querySelector(".login-message");
                    loginMessage.textContent = data.message;
                    loginMessage.style.color = "red";
                }
            })
            .catch(error => {
                console.error("Error:", error);
                const loginMessage = document.querySelector(".login-message");
                loginMessage.textContent = "エラーが発生しました。もう一度お試しください。";
                loginMessage.style.color = "red";
            });
        }); // Removed { once: true } to allow multiple attempts
    }
}
function hideLoginPopup() {
    console.log("hideLoginPopup function called!");
    const loginPopup = document.getElementById("loginPopup");
    if (loginPopup) {
        loginPopup.style.display = "none";
        document.body.classList.remove("blur");
    }
}
function togglePasswordVisibility() {
    const passwordField = document.getElementById("passwordField");
    const viewPasswordCheckbox = document.getElementById("viewPasswordCheckbox");
    if (passwordField && viewPasswordCheckbox) {
        passwordField.type = viewPasswordCheckbox.checked ? "text" : "password";
    } else {
        console.error("Password field or checkbox not found");
    }
}