const SUPABASE_URL = 'https://rhbpmopeylzyahwtmoyt.supabase.co';
const SUPABASE_KEY = 'sb_publishable_u4GK1vzg7c3vKfirV6snng_iLwBe7-z';
const feedList = document.querySelector('#feed-list');
const form = document.querySelector('#post-form');
const formStatus = document.querySelector('#form-status');
const refreshButton = document.querySelector('#refresh-feed');

const headers = {
  apikey: SUPABASE_KEY,
  Authorization: `Bearer ${SUPABASE_KEY}`,
  'Content-Type': 'application/json',
  Prefer: 'return=representation'
};

const escapeHTML = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;'
}[char]));

async function request(path, options = {}) {
  const response = await fetch(`${SUPABASE_URL}/rest/v1/${path}`, {
    ...options,
    headers: { ...headers, ...(options.headers || {}) }
  });

  if (!response.ok) {
    const detail = await response.text();
    throw new Error(detail || `Request failed with ${response.status}`);
  }

  return response.status === 204 ? null : response.json();
}

function renderPosts(posts) {
  if (!posts.length) {
    feedList.innerHTML = '<article class="post-card"><p>No posts yet. Be the first to share something useful.</p></article>';
    return;
  }

  feedList.innerHTML = posts.map((post) => `
    <article class="post-card">
      <header>
        <div>
          <h3>${escapeHTML(post.display_name)}</h3>
          <p>${new Date(post.created_at).toLocaleString()}</p>
        </div>
        <span class="badge">${escapeHTML(post.category)}</span>
      </header>
      <p>${escapeHTML(post.content)}</p>
    </article>
  `).join('');
}

async function loadPosts() {
  feedList.innerHTML = '<article class="post-card"><p>Loading Supabase feed...</p></article>';
  const posts = await request('public_posts?select=*&order=created_at.desc&limit=20');
  renderPosts(posts);
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const displayName = document.querySelector('#display-name').value.trim();
  const category = document.querySelector('#category').value;
  const content = document.querySelector('#content').value.trim();

  if (!displayName || !content) return;

  formStatus.textContent = 'Publishing...';
  try {
    await request('public_posts', {
      method: 'POST',
      body: JSON.stringify({ display_name: displayName, category, content })
    });
    form.reset();
    formStatus.textContent = 'Published to Supabase.';
    await loadPosts();
  } catch (error) {
    formStatus.textContent = 'Could not publish. Check Supabase policies.';
    console.error(error);
  }
});

refreshButton.addEventListener('click', () => {
  loadPosts().catch((error) => {
    feedList.innerHTML = '<article class="post-card"><p>Could not load posts from Supabase.</p></article>';
    console.error(error);
  });
});

loadPosts().catch((error) => {
  feedList.innerHTML = '<article class="post-card"><p>Could not load posts from Supabase.</p></article>';
  console.error(error);
});
