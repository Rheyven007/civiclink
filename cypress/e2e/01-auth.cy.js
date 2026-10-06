import { ROLES } from '../support/commands';

describe('Authentication', () => {
  it('sends signed-out visitors to the login page', () => {
    cy.visit('/#/proposals');
    cy.location('hash').should('eq', '#/login');
    cy.title().should('eq', 'Sign in · CivicLink');
  });

  it('shows the sign-in form and one Quick sign-in account per role', () => {
    cy.visit('/#/login');
    cy.get('form[data-form="login"]').within(() => {
      cy.get('input[name="email"]').should('have.attr', 'type', 'email').and('have.attr', 'required');
      cy.get('input[name="password"]').should('have.attr', 'type', 'password').and('have.attr', 'required');
      cy.get('input[name="remember"]').should('be.checked');
      cy.contains('button[type="submit"]', 'Sign in').should('be.visible');
    });
    Object.values(ROLES).forEach(({ label }) => cy.contains('.quick-acct', label).should('be.visible'));
    cy.contains('a', 'Create a citizen account').should('have.attr', 'href', '#/register');
  });

  it('rejects a wrong password and stays on the login page', () => {
    cy.loginWith('not-a-real-user@example.com', 'wrong-password');
    cy.get('form[data-form="login"] .form-err[role="alert"]')
      .should('be.visible')
      .and('contain.text', 'Incorrect email or password');
    cy.location('hash').should('eq', '#/login');
  });

  it('signs in with a Quick sign-in button', () => {
    cy.quickLogin('citizen');
    cy.title().should('eq', 'Community Hub · CivicLink');
  });

  it('signs each role in to its own home page', () => {
    Object.entries(ROLES).forEach(([role, { home }]) => {
      cy.loginAs(role);
      cy.visitApp('#/');
      cy.location('hash').should('eq', home);
      cy.get('.tn-me').should('contain.text', ROLES[role].label);
    });
  });

  it('signs out from the account menu', () => {
    cy.loginAs('citizen');
    cy.visitApp('#/feed');
    cy.get('[data-act="me-menu"]').click();
    cy.get('#modal').contains('button', 'Sign out').click();
    cy.location('hash').should('eq', '#/login');
    cy.shouldToast('Signed out');
    cy.visit('/#/feed');
    cy.location('hash').should('eq', '#/login');
  });
});

describe('Registration validation', () => {
  beforeEach(() => cy.visit('/#/register'));

  it('blocks submission when required fields are empty', () => {
    cy.contains('button[type="submit"]', 'Create account').click();
    cy.get('form[data-form="register"]').then(($f) => expect($f[0].checkValidity()).to.be.false);
    cy.get('input[name="full_name"]:invalid').should('exist');
    cy.location('hash').should('eq', '#/register');
  });

  it('requires an 8+ character password and the privacy consent', () => {
    cy.get('input[name="password"]').should('have.attr', 'minlength', '8');
    cy.get('input[name="agree"]').should('have.attr', 'required');
  });

  it('shows an error when the passwords do not match', () => {
    cy.get('form[data-form="register"]').within(() => {
      cy.get('input[name="full_name"]').type('Cypress Mismatch');
      cy.get('input[name="email"]').type(`mismatch.${Date.now()}@example.com`);
      cy.get('input[name="barangay"]').type('Poblacion');
      cy.get('input[name="password"]').type('password123');
      cy.get('input[name="confirm"]').type('different123');
      cy.get('input[name="agree"]').check();
      cy.root().submit();
      cy.get('.form-err').should('be.visible').and('contain.text', 'Passwords do not match');
    });
    cy.location('hash').should('eq', '#/register');
  });
});
