document.addEventListener('DOMContentLoaded', () => {
    const refreshButton = document.getElementById('refresh-data');
    const activityChartCanvas = document.getElementById('activityChart');
    let activityChart;

    // Initialize Chart
    const initChart = (data) => {
        if (activityChart) {
            activityChart.destroy();
        }

        const labels = data.activity.map(item => new Date(item.date).toLocaleDateString());
        const postCounts = data.activity.map(item => item.post_count);

        activityChart = new Chart(activityChartCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Posts',
                    data: postCounts,
                    borderColor: 'hsl(var(--primary-color-hue), 75%, 60%)',
                    backgroundColor: 'hsla(var(--primary-color-hue), 75%, 60%, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Posts'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    };

    // Update Dashboard
    const updateDashboard = (data) => {
        document.querySelector('.analytics-cards .card:nth-child(1) .count').textContent = data.counts.post_count;
        document.querySelector('.analytics-cards .card:nth-child(2) .count').textContent = data.counts.like_count;
        document.querySelector('.analytics-cards .card:nth-child(3) .count').textContent = data.counts.comment_count;

        const categoryList = document.querySelector('.analytics-categories .category-list');
        categoryList.innerHTML = data.categories.map(category => `
            <li>
                <span class="category-name">${category.category || 'No Category'}</span>
                <span class="category-count">${category.post_count} posts</span>
            </li>
        `).join('');

        initChart(data);
    };

    // Fetch Updated Data
    const fetchAnalyticsData = async () => {
        if (!window.csrfToken) {
            alert('CSRF token is missing. Please refresh the page.');
            return;
        }

        try {
            const response = await fetch('analytics_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken
                },
                body: JSON.stringify({ csrf_token: window.csrfToken })
            });

            const text = await response.text();
            try {
                const result = JSON.parse(text);
                if (result.success) {
                    updateDashboard(result.data);
                } else {
                    alert(result.message);
                }
            } catch (jsonError) {
                console.error('Raw response from analytics_data.php:', text);
                alert('Error fetching analytics data: Invalid server response. Check console for details.');
            }
        } catch (error) {
            alert('Error fetching analytics data: ' + error.message);
        }
    };

    // Initialize with existing data
    updateDashboard(window.analyticsData);

    // Refresh button event
    refreshButton.addEventListener('click', fetchAnalyticsData);
});