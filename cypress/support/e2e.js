import './commands';

// The frontend can throw recoverable errors after a failed API call.
// Let the assertions decide whether a test failed.
Cypress.on('uncaught:exception', (err) => {
  if (/Cannot reach the server|Server error|Request failed/.test(err.message)) return false;
});
