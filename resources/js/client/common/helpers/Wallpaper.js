// Minimal replacement for reddit-image-fetcher, which pulled in a vulnerable
// axios 0.x and allowed NSFW posts by default. Uses plain fetch so the admin
// API token is never attached to third-party requests.
const SUBREDDITS = [
    'wallpaper',
    'wallpapers',
    'EarthPorn',
    'SkyPorn',
    'ImaginaryLandscapes',
    'ImaginaryStarscapes',
    'ImaginaryCityscapes',
];

const LISTINGS = ['hot', 'top', 'rising'];

const IMAGE_URL = /^https:\/\/\S+\.(jpe?g|png)$/i;

const pick = (items) => items[Math.floor(Math.random() * items.length)];

const toWallpaper = (post) => ({
    image: post.url,
    thumbnail: IMAGE_URL.test(post.thumbnail || '') ? post.thumbnail : post.url,
    title: post.title,
    postLink: `https://www.reddit.com${post.permalink}`,
});

const fetchWallpaper = async (attempts = 3) => {
    for (let attempt = 0; attempt < attempts; attempt++) {
        const url = `https://api.reddit.com/r/${pick(SUBREDDITS)}/${pick(LISTINGS)}?limit=75`;
        const response = await fetch(url, { credentials: 'omit' });

        if (!response.ok) {
            continue;
        }

        const body = await response.json();
        const posts = ((body && body.data && body.data.children) || [])
            .map((child) => child && child.data)
            .filter((post) => post && !post.over_18 && IMAGE_URL.test(post.url || ''));

        if (posts.length) {
            return toWallpaper(pick(posts));
        }
    }

    return null;
};

export default fetchWallpaper;
