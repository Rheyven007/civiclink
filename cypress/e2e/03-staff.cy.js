// Read-only checks for sector representative, LGU officer and administrator portals.
const pages = (list) => list.forEach(([hash, title]) => {
  it(`opens ${title} (${hash})`, () => {
    cy.visitApp(hash);
    cy.location('hash').should('eq', hash);
    cy.shouldShowPage(title);
  });
});

describe('Sector representative portal', () => {
  beforeEach(() => cy.loginAs('sector'));
  pages([['#/sector', 'Sector Hub'], ['#/sector/endorse', 'Endorsements']]);

  it('switches between endorsement tabs', () => {
    cy.visitApp('#/sector/endorse');
    cy.contains('.tabs button', 'Awaiting endorsement').should('have.class', 'on');
    cy.contains('.tabs button', 'Endorsed by me').click();
    cy.contains('.tabs button', 'Endorsed by me').should('have.class', 'on');
  });
});

describe('LGU officer portal', () => {
  beforeEach(() => cy.loginAs('lgu'));
  pages([['#/lgu', 'Overview'], ['#/lgu/cases', 'Case Management'], ['#/lgu/sla', 'SLA & Performance'], ['#/admin/reports', 'Reports & Analytics']]);

  it('has case tabs for requests, complaints and proposals', () => {
    cy.visitApp('#/lgu/cases');
    ['service_request', 'complaint', 'proposal'].forEach((type) => {
      cy.get(`.seg a[href="#/lgu/cases/${type}"]`).click();
      cy.location('hash').should('eq', `#/lgu/cases/${type}`);
      cy.get('table.tbl thead').should('contain.text', 'Reference').and('contain.text', 'Status');
    });
  });

  it('cannot create reports from the citizen composer', () => {
    cy.visitApp('#/lgu');
    cy.get('.topnav [data-act="compose"]').should('not.exist');
  });
});

describe('Administrator portal', () => {
  beforeEach(() => cy.loginAs('admin'));
  pages([
    ['#/admin', 'Overview'],
    ['#/admin/users', 'User Management'],
    ['#/admin/sectors', 'Sector Management'],
    ['#/admin/consultations', 'Consultation Moderation'],
    ['#/admin/reports', 'Reports & Analytics'],
    ['#/admin/audit', 'Audit Logs'],
    ['#/admin/settings', 'Settings'],
    ['#/lgu/cases', 'Case Management'],
  ]);
});

describe('Role-based access', () => {
  const restricted = (role, hash) => it(`blocks ${role} from ${hash}`, () => {
    cy.loginAs(role);
    cy.visitApp(hash);
    cy.title().should('eq', 'Restricted · CivicLink');
    cy.get('#main').should('contain.text', "You don't have access to this page");
  });

  restricted('citizen', '#/lgu/cases');
  restricted('citizen', '#/admin/users');
  restricted('citizen', '#/sector/endorse');
  restricted('sector', '#/lgu');
  restricted('lgu', '#/admin/users');
  restricted('lgu', '#/requests');
});
