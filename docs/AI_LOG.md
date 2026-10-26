# AI Log

## Milestone 1

### Tools and tasks

- **OpenCode with DeepSeek V4.1 Flash Free** (Plan agent, then Frontend agent): the static frontend, meaning the SPA shell, the hash router, the 12 view pages, the styling and the home page scripts.
- **Claude (chat assistant, claude.ai):** the documentation of this project, advices for overall workflow of the project.

**1. SPA shell, router and views (Session 1)**
- Tool and agent: OpenCode, Plan agent for the plan, then the Frontend agent to carry it out. 
- Main prompt (shortened): "js/router.js: hash router using jQuery. On load and on hashchange, read location.hash (default #home) and map it to views/<name>.html. Fetch each view with $.get only the first time, [...] remember it in a cache object. Afterwards only toggle d-none."
- Generated: `index.html`, `css/style.css`, `js/router.js`, `js/app.js` and the 12 view pages in `frontend/views/`.
- Kept: the rest of the plan.
- Changed: I corrected the plan before it started (see "Where the AI was wrong", cases 1 to 3).
- Rejected: the "13 fragments", the extra concurrency guard and the browser verification step in the plan.

**2. "Recently added" scrolling cards on the home page**
- Tool and agent: OpenCode, Frontend agent.
- Main prompt: "Change the 'Recently added' section to be cards that are infinitely scrolling. Make it span full length of the screen."
- Generated: `js/home.js` and the matching animation rules in `css/style.css`.
- Kept: the continuously scrolling card row.

**3. Sticky navbar that protracts and retracts**
- Tool and agent: OpenCode, Frontend agent.
- Main prompt: "For the navigation bar, make the bar stick to the top as the user scrolls, and while scrolling the navbar
should slightly retract to make more space for the rest of the content and when the user is at the top of the page, then 
the navbar should take it's full size.
- Generated: `js/navbar.js`, which keeps the navbar fixed while scrolling and makes it shrink slightly. 
- Kept: navbar being sticky.
- Rejected: the logic of protraction and retraction of the navbar.

### Where the AI was wrong

1. **The plan counted 13 view pages instead of 12.** The plan said "13 view fragments" twice, but the list it gave had 12 names, and my prompt asked for 12.
   - *How I noticed:* I counted the names in the plan against my prompt.
   - *How I fixed it:* I replied "12 view fragments, not 13" and listed them before telling it to go ahead.

2. **The plan added a feature I did not ask for.** The plan included a "latest request wins" guard in `router.js` that was not in my prompt.
   - *How I noticed:* I compared each plan bullet with my prompt.
   - *How I fixed it:* I told it to leave the guard out and keep the router simple, so I can explain every function of it.

3. **The plan still included a browser check after I told it not to.** I had told it not to open or verify anything in the browser, but the corrected plan still ended with a step to verify the pages via the local app URL.
   - *How I noticed:* I read bullet 8 of the corrected plan.
   - *How I fixed it:* I repeated that it must not run commands or verify anything itself, and I tested the pages in the browser myself.

4. **The "Recently added" row sometimes had a missing card on the right.** The first version of the scrolling row left empty space on the right for a couple of seconds, especially on wide screens.
   - *How I noticed:* after scrolling down the home page I saw an empty gap on the right side of the section.
   - *How I fixed it:* I told the agent that a card was sometimes missing on the right. It replaced the "copy the cards once and start" logic in `js/home.js` with a function, `updateRecentLoop()`, that measures the width of one group of cards and of the visible row, adds copies until the row is always full, and sets the loop distance (`--home-marquee-shift`) to exactly one group width. The animation in `css/style.css` now ends at that measured value instead of a hard-coded -50%. It also removed a leftover duplicate `@keyframes` block that still used -50% and would have overridden the new rule. The copies are skipped on phones and when the user prefers reduced motion, and the row rebuilds when the window is resized.

5. **The navbar shook when scrolling back to the top.** The sticky navbar shrinks when you scroll down and should protract again at the top of the page, but near the top it kept flickering between the two states.
   - *How I noticed:* I scrolled down the page and then back up, and the navbar started shaking as it protracted.
   - *How I fixed it:* I told the agent that the navbar had problems protracting and shook. The cause was in `js/navbar.js`: it switched the compact state at a single scroll position (8px), and the compact header is about 11px shorter, which moves the page content and changes the scroll position again, so the state flipped back and forth. The agent replaced the single threshold with two: the compact state is turned on only below 32px of scrolling is passed, and turned off only below 4px. Between the two values the current state is kept, so one small shift can no longer flip it.