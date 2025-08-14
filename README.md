# No Thumbnail Cleaner

**No Thumbnail Cleaner** is a super lightweight and simple WordPress plugin designed to clean up posts without featured images.  

It scans all your posts and moves those without a thumbnail to the trash. The plugin processes posts in small batches to avoid overloading the server, making it ideal for massive WordPress sites.  

---

## Features

- Clean and lightweight (no database overhead).
- Processes posts in batches of 20 to avoid timeouts or crashes.
- Real-time progress indicator.
- Designed for large sites (tested on sites with over 200,000 posts).
- No configuration needed – just install and run.

---

## How It Works

1. Go to the admin menu and click **“No Thumbnail Cleaner”**.
2. Click the **“Start Cleanup”** button.
3. Leave your browser tab open. The plugin will process all posts in the background.
4. Posts without featured images will be sent to the trash.

---

## Why Use It?

On massive WordPress sites, cleaning up posts without thumbnails can be slow and painful.  
This plugin works by analyzing posts in **small batches**, avoiding server overload and ensuring the process finishes even with thousands of posts.  

In our tests, it successfully trashed **thousands of posts without thumbnails** on sites with more than **200,000 posts** – all in a single run.  

---

## Notes

- Designed for **published posts** only (`post_status = publish`).
- Leaves all other content types (pages, custom post types) untouched.
- You can restart the process as many times as needed – it’s safe.

---

## License

This plugin is open source and released under the MIT License.
