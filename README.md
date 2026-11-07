# Algorithm Demo Courses (algorithm.tsp.edu.rs)

Public demo repository for the Algorithm Flowcharts suite of Moodle plugins:
- [tiny_algorithm](https://github.com/blagojevicboban/moodle-tiny_algorithm): Visual flowchart editor in TinyMCE.
- [filter_algorithm](https://github.com/blagojevicboban/moodle-filter_algorithm): Text filter that turns SVG flowcharts into interactive step-by-step runners with variable watches.
- [qtype_algorithm](https://github.com/blagojevicboban/moodle-qtype_algorithm): Automatically graded flowchart questions for Moodle quizzes.

## Contents

- `demo.php`: Automated CLI restore script that setups the site, customizes the Boost theme, sets cross-linking banners, updates the front page with dual course cards, and enrolls demo users (`teacher` / `student`).
- `algoritam-demo.mbz`: English demo course (`ALGORITHM-DEMO` – *Algorithms with flowcharts: demo (English)*).
- `algoritam-demo-sr.mbz`: Serbian demo course (`ALGORITHM-DEMO-SR` – *Algoritmi sa dijagramima toka: demo (srpski)*).
- `deploy/`: GitHub webhook server and automated deploy script.

## Webhook Auto-Deploy Setup (Virtualmin)

1. Clone this repository on the server:
   ```bash
   cd /home/algoritam
   git clone https://github.com/blagojevicboban/algorithm-demo.git demo-repo
   cd demo-repo
   ```

2. Start the webhook server under PM2:
   ```bash
   pm2 start deploy/ecosystem.config.js
   pm2 save
   ```

3. In Virtualmin Apache Configuration for `algorithm.tsp.edu.rs`:
   Add inside the VirtualHost (or via Webmin → Apache Webserver):
   ```apache
   ProxyPass /webhook http://127.0.0.1:9011/webhook
   ProxyPassReverse /webhook http://127.0.0.1:9011/webhook
   ```

4. In GitHub Repo Settings → Webhooks:
   - **Payload URL:** `https://algorithm.tsp.edu.rs/webhook`
   - **Content type:** `application/json`
   - **Secret:** `algorithm-demo-secret-2026`
   - **Events:** Just the `push` event.

Now every `git push` to `main` will automatically update `algorithm.tsp.edu.rs`!
