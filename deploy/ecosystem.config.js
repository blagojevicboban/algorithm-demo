module.exports = {
  apps: [{
    name: 'algorithm-webhook',
    script: 'deploy/webhook-server.js',
    cwd: __dirname + '/..',
    env: {
      NODE_ENV: 'production',
      WEBHOOK_PORT: 9011,
      WEBHOOK_SECRET: process.env.WEBHOOK_SECRET || 'algorithm-demo-secret-2026',
      TARGET_BRANCH: 'main'
    },
    autorestart: true,
    watch: false,
    max_memory_restart: '150M'
  }]
};
