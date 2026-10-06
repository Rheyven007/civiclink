// CivicLink custom commands
// Roles, labels and home routes mirror js/app.js (ROLE_LABEL and HOME).
export const ROLES = {
  citizen: { label: 'Citizen', home: '#/feed' },
  sector: { label: 'Sector Representative', home: '#/feed' },
  lgu: { label: 'LGU Officer', home: '#/lgu' },
  admin: { label: 'Administrator', home: '#/admin' },
};

export const apiUrl = (action) =>
  `${Cypress.config('baseUrl').replace(/\/$/, '')}/api/index.php?action=${action}`;

const API_ROLE = { citizen: 'citizen', sector: 'sector_rep', lgu: 'lgu_officer', admin: 'admin' };

/** Sign in as a role through the API (fast and reliable) and cache the PHP session with cy.session.
 *  Credentials come from the Quick sign-in list (QUICK_LOGIN in api/config.php), or from
 *  `expose.accounts` in cypress.config.js, e.g. { citizen: { email: '...', password: '...' } }. */
Cypress.Commands.add('loginAs', (role) => {
  if (!ROLES[role]) throw new Error(`Unknown role "${role}". Use: ${Object.keys(ROLES).join(', ')}`);
  cy.session(['civiclink', role], () => {
    const fixed = (Cypress.expose('accounts') || {})[role];
    const creds = fixed
      ? cy.wrap(fixed, { log: false })
      : cy.request(apiUrl('bootstrap')).then(({ body }) => {
        const acct = (body.quick_login || []).find((a) => a.role === API_ROLE[role]);
        if (!acct) throw new Error(`No Quick sign-in account for ${ROLES[role].label}. Enable QUICK_LOGIN or set expose.accounts.${role}.`);
        return acct;
      });
    creds.then(({ email, password }) => {
      cy.request({
        method: 'POST',
        url: apiUrl('login'),
        headers: { 'X-CivicLink': '1' },
        body: { email, password, remember: false },
      }).its('body.me').should('be.a', 'number');
    });
  }, {
    validate() {
      cy.request(apiUrl('bootstrap')).its('body.me').should('be.a', 'number');
    },
  });
});

/** Sign in by clicking a Quick sign-in button on the login page (tests the real UI flow). */
Cypress.Commands.add('quickLogin', (role) => {
  cy.visit('/#/login');
  cy.contains('.quick-acct', ROLES[role].label, { timeout: 15000 }).click();
  cy.location('hash', { timeout: 15000 }).should('eq', ROLES[role].home);
});

/** Sign in with an email and password through the real form. */
Cypress.Commands.add('loginWith', (email, password) => {
  cy.visit('/#/login');
  cy.get('form[data-form="login"]').within(() => {
    cy.get('input[name="email"]').clear().type(email);
    cy.get('input[name="password"]').clear().type(password, { log: false });
    cy.root().submit();
  });
});

/** Visit a route as the signed-in user. Skips the first-run tour and community reminder,
 *  and accepts the Terms of Use gate if this account hasn't accepted the current version. */
Cypress.Commands.add('visitApp', (hash) => {
  cy.request(apiUrl('bootstrap')).then(({ body }) => {
    cy.visit(`/${hash}`, {
      onBeforeLoad(win) {
        win.localStorage.setItem('civiclink.theme', 'light');
        if (body.me) {
          win.localStorage.setItem(`civiclink.tour.${body.me}`, '1');
          win.sessionStorage.setItem(`civiclink.reminded.${body.me}`, '1');
        }
      },
    });
  });
  cy.get('#app .shell, #app .auth, #app .legal-public', { timeout: 15000 }).should('exist');
  cy.acceptTermsIfShown();
});

Cypress.Commands.add('acceptTermsIfShown', () => {
  cy.get('body').then(($body) => {
    if (!$body.find('.terms-wrap').length) return;
    cy.get('#terms-form input[type="checkbox"]').check({ force: true });
    cy.get('#terms-form button[type="submit"]').click();
    cy.get('.terms-wrap', { timeout: 10000 }).should('not.exist');
  });
});

/** Assert the current page rendered properly (title set, no error/404/restricted screen). */
Cypress.Commands.add('shouldShowPage', (title) => {
  cy.title().should('eq', `${title} · CivicLink`);
  cy.get('#main').should('not.contain.text', 'Something went wrong')
    .and('not.contain.text', 'Page not found')
    .and('not.contain.text', "You don't have access to this page");
});

/** Open the "Report a Concern" composer and choose a form: request | complaint | proposal. */
Cypress.Commands.add('openComposer', (kind) => {
  cy.get('.topnav [data-act="compose"]').click();
  cy.get('#modal').should('contain.text', 'What would you like to do?');
  cy.get(`#modal [data-act="compose-${kind}"]`).click();
  cy.get(`#modal form[data-form="new-${kind}"]`).should('be.visible');
});

Cypress.Commands.add('shouldToast', (text) => {
  cy.get('#toasts', { timeout: 10000 }).should('contain.text', text);
});

/** Unique text for records created by tests, so reruns never collide. */
Cypress.Commands.add('uid', () => cy.wrap(`${Date.now().toString(36)}${Cypress._.random(100, 999)}`));
