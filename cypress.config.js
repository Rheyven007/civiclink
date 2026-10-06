const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    // Where CivicLink is served. Override with:  CIVICLINK_URL=http://localhost/civiclink/ npx cypress run
    baseUrl: process.env.CIVICLINK_URL || 'http://localhost:3000/',
    specPattern: 'cypress/e2e/**/*.cy.js',
    supportFile: 'cypress/support/e2e.js',
    viewportWidth: 1280,
    viewportHeight: 800,
    video: false,
    screenshotOnRunFailure: true,
    defaultCommandTimeout: 10000,
    pageLoadTimeout: 30000,
    retries: { runMode: 1, openMode: 0 },
    expose: {
      // Set to true to skip the workflow spec that creates database records:
      //   npx cypress run --expose readOnly=true
      readOnly: false,
      // Optional fixed test accounts if QUICK_LOGIN is off, e.g.
      // accounts: { citizen: { email: 'citizen@civiclink.gov', password: 'password' } },
    },
  },


  reporter: 'mochawesome',
reporterOptions: {
  reportDir: 'cypress/reports',
  overwrite: false,
  html: false,
  json: true,
},

});
