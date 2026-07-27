document.addEventListener('DOMContentLoaded', () => {
    const refreshButton = document.getElementById('refresh-data');
    const exportButton = document.getElementById('export-report');
    const timeframeSelect = document.getElementById('timeframe-select');
    const activityChartCanvas = document.getElementById('activityChart');
    const categoryChartCanvas = document.getElementById('categoryChart');

    let activityChart = null;
    let categoryChart = null;

    const chartColors = [
        '#3b82f6', '#ec4899', '#10b981', '#f59e0b', '#8b5cf6',
        '#6366f1', '#06b6d4', '#f43f5e', '#14b8a6'
    ];

    // Helper: Is dark mode active?
    const isDarkMode = () => document.body.classList.contains('dark-mode');

    // Toast notification
    const showToast = (message, isError = false) => {
        const toast = document.createElement('div');
        toast.className = 'analytics-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${isError ? '#ef4444' : '#10b981'};
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 9999;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(10px);
        `;
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // Animated counter helper
    const animateValue = (element, start, end, duration = 800, suffix = '') => {
        if (!element) return;
        const range = end - start;
        let current = start;
        const increment = end > start ? 1 : -1;
        const stepTime = Math.abs(Math.floor(duration / (range || 1)));
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = (Number.isInteger(current) ? current : current.toFixed(1)) + suffix;
        }, Math.max(stepTime, 16));
    };

    // Render Activity Line Chart
    const initActivityChart = (activityData) => {
        if (!activityChartCanvas) return;

        if (activityChart) {
            activityChart.destroy();
        }

        const ctx = activityChartCanvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.45)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        const labels = (activityData || []).map(item => {
            const d = new Date(item.date);
            return isNaN(d) ? item.date : d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        });
        const counts = (activityData || []).map(item => parseInt(item.post_count, 10));

        const textColor = isDarkMode() ? '#9ca3af' : '#4b5563';
        const gridColor = isDarkMode() ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

        activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [{
                    label: 'Posts Published',
                    data: counts.length ? counts : [0],
                    borderColor: '#3b82f6',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDarkMode() ? '#1f2937' : '#ffffff',
                        titleColor: isDarkMode() ? '#f3f4f6' : '#111827',
                        bodyColor: isDarkMode() ? '#d1d5db' : '#374151',
                        borderColor: isDarkMode() ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor, precision: 0 },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                }
            }
        });
    };

    // Render Category Doughnut Chart
    const initCategoryChart = (categoriesData) => {
        if (!categoryChartCanvas) return;

        if (categoryChart) {
            categoryChart.destroy();
        }

        const ctx = categoryChartCanvas.getContext('2d');
        const labels = (categoriesData || []).map(c => c.category || 'Uncategorized');
        const counts = (categoriesData || []).map(c => parseInt(c.post_count, 10));

        categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels.length ? labels : ['No Posts'],
                datasets: [{
                    data: counts.length ? counts : [1],
                    backgroundColor: chartColors.slice(0, Math.max(labels.length, 1)),
                    borderWidth: 2,
                    borderColor: isDarkMode() ? '#1e1b2e' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => ` ${context.label}: ${context.raw} post(s)`
                        }
                    }
                }
            }
        });
    };

    // Update Category Progress List UI
    const updateCategoryProgressList = (categoriesData) => {
        const categoryListEl = document.getElementById('category-progress-list');
        if (!categoryListEl) return;

        const totalSum = (categoriesData || []).reduce((acc, curr) => acc + parseInt(curr.post_count, 10), 0) || 1;

        categoryListEl.innerHTML = (categoriesData || []).map(cat => {
            const count = parseInt(cat.post_count, 10);
            const pct = Math.round((count / totalSum) * 100);
            const catName = cat.category || 'Uncategorized';
            return `
                <li class="category-progress-item">
                    <div class="category-info">
                        <span class="category-name-tag">${catName}</span>
                        <span class="category-count-badge">${count} (${pct}%)</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: ${pct}%;"></div>
                    </div>
                </li>
            `;
        }).join('') || '<li class="text-xs text-gray-400">No categories found</li>';
    };

    // Update Top Spotlight Post Card
    const updateSpotlightCard = (spotlightData) => {
        const container = document.getElementById('spotlight-container');
        if (!container) return;

        if (spotlightData && spotlightData.content) {
            const snippet = spotlightData.content.length > 140 ? spotlightData.content.substring(0, 140) + '...' : spotlightData.content;
            container.innerHTML = `
                <p class="spotlight-text">"${snippet}"</p>
                <div class="spotlight-stats">
                    <span class="spotlight-stat likes"><i class="bi bi-heart-fill"></i> ${spotlightData.like_count || 0} likes</span>
                    <span class="spotlight-stat comments"><i class="bi bi-chat-dots-fill"></i> ${spotlightData.comment_count || 0} comments</span>
                </div>
            `;
        } else {
            container.innerHTML = `<p class="spotlight-text text-gray-400">No posts published yet.</p>`;
        }
    };

    // Update Recent Activity Stream
    const updateActivityStream = (activities) => {
        const streamEl = document.getElementById('timeline-stream');
        if (!streamEl) return;

        if (activities && activities.length) {
            streamEl.innerHTML = activities.map(act => {
                const isPost = act.type === 'post';
                const snippet = act.content.length > 70 ? act.content.substring(0, 70) + '...' : act.content;
                const d = new Date(act.created_at);
                const timeStr = isNaN(d) ? act.created_at : d.toLocaleDateString() + ' • ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                return `
                    <div class="timeline-item">
                        <div class="timeline-icon ${isPost ? 'type-post' : 'type-comment'}">
                            <i class="bi ${isPost ? 'bi-file-earmark-plus' : 'bi-chat-left-text'}"></i>
                        </div>
                        <div class="timeline-content">
                            <span class="timeline-title">${isPost ? 'Published a new post' : 'Added a comment'}</span>
                            <span class="timeline-snippet">${snippet}</span>
                            <span class="timeline-time">${timeStr}</span>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            streamEl.innerHTML = `<p class="text-sm text-gray-400">No recent activity recorded.</p>`;
        }
    };

    // Update Category Deep-Dive Breakdown Table
    const updateCategoryTable = (categoriesData) => {
        const tbody = document.getElementById('category-table-body');
        if (!tbody) return;

        const totalSum = (categoriesData || []).reduce((acc, curr) => acc + parseInt(curr.post_count, 10), 0) || 1;

        if (categoriesData && categoriesData.length) {
            tbody.innerHTML = categoriesData.map(cat => {
                const count = parseInt(cat.post_count, 10);
                const pct = Math.round((count / totalSum) * 100);
                const catName = cat.category || 'Uncategorized';
                return `
                    <tr>
                        <td><strong>${catName}</strong></td>
                        <td>${count} posts</td>
                        <td><span class="badge-growth">${pct}%</span></td>
                        <td>
                            <div class="deepdive-bar-bg">
                                <div class="deepdive-bar-fill" style="width: ${pct}%;"></div>
                            </div>
                            <span>${pct}% share</span>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-gray-400 text-center">No category data available.</td></tr>`;
        }
    };

    // Update Creator Milestones List
    const updateMilestones = (milestones) => {
        const container = document.getElementById('milestones-list');
        if (!container || !milestones) return;

        container.innerHTML = milestones.map(ms => `
            <div class="milestone-item">
                <div class="milestone-header">
                    <span class="milestone-title"><i class="bi ${ms.icon}"></i> ${ms.title}</span>
                    <span class="milestone-progress-text">${ms.current} / ${ms.target} (${ms.percent}%)</span>
                </div>
                <div class="milestone-bar-wrapper">
                    <div class="milestone-bar-fill" style="width: ${ms.percent}%;"></div>
                </div>
            </div>
        `).join('');
    };

    // Update Peak Engagement Grid
    const updatePeakInsights = (peakData) => {
        if (!peakData) return;
        const bestDayEl = document.getElementById('peak-best-day');
        const peakHoursEl = document.getElementById('peak-hours');
        const velocityEl = document.getElementById('peak-velocity');
        const rankEl = document.getElementById('peak-rank');

        if (bestDayEl) bestDayEl.textContent = peakData.best_day || 'N/A';
        if (peakHoursEl) peakHoursEl.textContent = peakData.peak_hours || 'N/A';
        if (velocityEl) velocityEl.textContent = peakData.response_velocity || 'N/A';
        if (rankEl) rankEl.textContent = peakData.community_rank || 'N/A';
    };

    // Update Entire Dashboard
    const updateDashboard = (data) => {
        if (!data) return;

        // Store updated data globally for CSV export
        window.analyticsData = data;

        const counts = data.counts || {};
        
        // Update Stat Cards with animated count
        const postsEl = document.getElementById('stat-posts');
        const likesEl = document.getElementById('stat-likes');
        const commentsEl = document.getElementById('stat-comments');
        const engagementEl = document.getElementById('stat-engagement');
        const bookmarksEl = document.getElementById('stat-bookmarks');
        const impactScoreVal = document.getElementById('impact-score-val');

        if (postsEl) animateValue(postsEl, 0, parseInt(counts.post_count || 0, 10));
        if (likesEl) animateValue(likesEl, 0, parseInt(counts.like_count || 0, 10));
        if (commentsEl) animateValue(commentsEl, 0, parseInt(counts.comment_count || 0, 10));
        if (engagementEl) animateValue(engagementEl, 0, parseFloat(counts.engagement_rate || 0), 800, '%');
        if (bookmarksEl) animateValue(bookmarksEl, 0, parseInt(counts.bookmark_count || 0, 10));
        if (impactScoreVal) animateValue(impactScoreVal, 0, parseInt(counts.impact_score || 0, 10));

        // Update Period Label
        const periodLabel = document.getElementById('activity-period-label');
        if (periodLabel && timeframeSelect) {
            const selectedOpt = timeframeSelect.options[timeframeSelect.selectedIndex];
            periodLabel.textContent = selectedOpt ? selectedOpt.text : 'Last 30 Days';
        }

        initActivityChart(data.activity);
        initCategoryChart(data.categories);
        updateCategoryProgressList(data.categories);
        updateCategoryTable(data.categories);
        updateMilestones(data.milestones);
        updatePeakInsights(data.peak_insights);
        updateSpotlightCard(data.most_liked_post);
        updateActivityStream(data.recent_activity);
    };

    // Fetch Dashboard Data from API
    const fetchAnalyticsData = async (days = 30) => {
        if (refreshButton) {
            refreshButton.disabled = true;
            refreshButton.classList.add('loading');
            refreshButton.innerHTML = `<i class="bi bi-arrow-repeat spin"></i> Updating...`;
        }

        try {
            const response = await fetch('analytics_connect.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken || ''
                },
                body: JSON.stringify({
                    csrf_token: window.csrfToken || '',
                    days: parseInt(days, 10)
                })
            });

            const result = await response.json();
            if (result.success && result.data) {
                updateDashboard(result.data);
                showToast('Analytics dashboard updated!');
            } else {
                showToast(result.message || 'Failed to update analytics', true);
            }
        } catch (err) {
            console.error('Fetch error:', err);
            showToast('Error refreshing analytics data', true);
        } finally {
            if (refreshButton) {
                refreshButton.disabled = false;
                refreshButton.classList.remove('loading');
                refreshButton.innerHTML = `<i class="bi bi-arrow-clockwise"></i> Refresh`;
            }
        }
    };

    // Export Report Handler
    const handleExport = () => {
        if (!window.analyticsData || !window.analyticsData.counts) {
            showToast('No data available to export', true);
            return;
        }

        const counts = window.analyticsData.counts;
        const categories = window.analyticsData.categories || [];
        const dateStr = new Date().toISOString().split('T')[0];

        let csvContent = `Debuglia Analytics Summary Report (${dateStr})\n\n`;
        csvContent += `Metric,Value\n`;
        csvContent += `Total Posts,${counts.post_count || 0}\n`;
        csvContent += `Total Likes,${counts.like_count || 0}\n`;
        csvContent += `Total Comments,${counts.comment_count || 0}\n`;
        csvContent += `Engagement Rate,${counts.engagement_rate || 0}%\n`;
        csvContent += `Bookmarks Received,${counts.bookmark_count || 0}\n\n`;
        csvContent += `Posts by Category:\n`;
        csvContent += `Category,Post Count\n`;
        categories.forEach(c => {
            csvContent += `"${c.category || 'Uncategorized'}",${c.post_count}\n`;
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `debuglia_analytics_${dateStr}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showToast('Analytics report downloaded!');
    };

    // Theme toggle re-render listener
    const observer = new MutationObserver(() => {
        if (window.analyticsData && window.analyticsData.activity) {
            initActivityChart(window.analyticsData.activity);
            initCategoryChart(window.analyticsData.categories);
        }
    });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    // Event Listeners
    if (refreshButton) {
        refreshButton.addEventListener('click', () => {
            const days = timeframeSelect ? timeframeSelect.value : 30;
            fetchAnalyticsData(days);
        });
    }

    if (timeframeSelect) {
        timeframeSelect.addEventListener('change', (e) => {
            fetchAnalyticsData(e.target.value);
        });
    }

    if (exportButton) {
        exportButton.addEventListener('click', handleExport);
    }

    // Initial render from SSR window.analyticsData
    if (window.analyticsData) {
        updateDashboard(window.analyticsData);
    }
});