// Full end-to-end workflows. These CREATE records (proposals, requests, complaints,
// endorsements, decisions, a user) — run them against a test copy of the database.
// Skip them with:  npx cypress run --expose readOnly=true
// Tests in this file run in order and share the IDs created earlier.
const suite = Cypress.expose('readOnly') ? describe.skip : describe;

suite('Workflows (writes to the database)', { retries: 0 }, () => {
  const run = `${Date.now().toString(36)}`;
  const proposalTitle = `Cypress solar streetlights ${run}`;
  const complaintSubject = `Cypress complaint ${run}`;
  const requestLocation = `Cypress test site ${run}`;
  const justification = `Crew scheduled for inspection this week (${run}).`;
  let proposalId;
  let requestId;
  const idFromHash = (prefix) => cy.location('hash').should('match', new RegExp(`^${prefix}\\d+$`))
    .then((h) => Number(h.split('/').pop()));

  it('citizen publishes a community proposal', () => {
    cy.loginAs('citizen');
    cy.visitApp('#/feed');
    cy.openComposer('proposal');
    cy.get('#modal form[data-form="new-proposal"]').within(() => {
      cy.get('input[name="title"]').type(proposalTitle);
      cy.get('select[name="category"]').select('infrastructure');
      cy.get('textarea[name="description"]').type('Install solar-powered streetlights along the barangay road.');
      cy.get('textarea[name="justification"]').type('The road is dark at night and unsafe for students.');
      cy.get('textarea[name="expected_benefits"]').type('Safer streets and lower electricity costs.');
      cy.get('input[name="legal_ok"]').check();
      cy.root().submit();
    });
    cy.shouldToast('Proposal published');
    idFromHash('#/proposals/').then((id) => { proposalId = id; });
    cy.get('#main').should('contain.text', proposalTitle);
  });

  it('citizen submits a service request that is routed automatically', () => {
    cy.loginAs('citizen');
    cy.visitApp('#/feed');
    cy.openComposer('request');
    cy.get('#modal form[data-form="new-request"]').within(() => {
      cy.get('select[name="service_type"]').select('Streetlight repair');
      cy.get('select[name="priority"]').select('high');
      cy.get('input[name="location"]').type(requestLocation);
      cy.get('textarea[name="description"]').type('Streetlight has been out for a week near the chapel.');
      cy.get('input[name="legal_ok"]').check();
      cy.root().submit();
    });
    cy.shouldToast('submitted');
    idFromHash('#/requests/').then((id) => {
      requestId = id;
      cy.get('#main').should('contain.text', `REQ-${String(id).padStart(4, '0')}`);
    });
    cy.visitApp('#/requests');
    cy.get('#main').should('contain.text', 'Streetlight repair');
  });

  it('citizen files a complaint', () => {
    cy.loginAs('citizen');
    cy.visitApp('#/feed');
    cy.openComposer('complaint');
    cy.get('#modal form[data-form="new-complaint"]').within(() => {
      cy.get('input[name="subject"]').type(complaintSubject);
      cy.get('select[name="category"]').select('facility');
      cy.get('textarea[name="details"]').type('The barangay hall ramp is blocked by parked motorcycles every morning.');
      cy.get('input[name="legal_ok"]').check();
      cy.root().submit();
    });
    cy.shouldToast('filed');
    idFromHash('#/complaints/');
    cy.get('#main').should('contain.text', complaintSubject);
  });

  it('sector representative endorses the new proposal', () => {
    cy.loginAs('sector');
    cy.visitApp('#/sector/endorse');
    cy.get(`article[data-pid="${proposalId}"]`).find('[data-act="endorse"]').click();
    cy.get('#modal form[data-form="endorse"]').within(() => {
      cy.get('textarea[name="statement"]').type('Our members walk this road every night after school.');
      cy.get('input[name="legal_ok"]').check();
      cy.root().submit();
    });
    cy.shouldToast('Endorsed on behalf of your sector');
    cy.contains('.tabs button', 'Endorsed by me').click();
    cy.get(`article[data-pid="${proposalId}"]`).should('exist');
  });

  it('LGU officer finds the request and records a decision', () => {
    cy.loginAs('lgu');
    cy.visitApp('#/lgu/cases/service_request');
    cy.get('table.tbl').contains('tr', `REQ-${String(requestId).padStart(4, '0')}`).click();
    cy.location('hash').should('eq', `#/lgu/cases/service_request/${requestId}`);
    cy.get('form[data-form="decide"]').within(() => {
      cy.get('input[name="to"][value="in_progress"]').check();
      cy.get('textarea[name="justification"]').type(justification);
      cy.root().submit();
    });
    cy.shouldToast('Decision recorded');
    cy.get('.case-hero .badge').first().should('contain.text', 'In Progress');
    cy.get('#main').should('contain.text', justification);
  });

  it('LGU officer rejects a decision without a long enough justification', () => {
    cy.loginAs('lgu');
    cy.visitApp(`#/lgu/cases/service_request/${requestId}`);
    cy.get('form[data-form="decide"] textarea[name="justification"]')
      .should('have.attr', 'minlength', '15')
      .and('have.attr', 'required');
  });

  it('LGU officer moves the proposal to review', () => {
    cy.loginAs('lgu');
    cy.visitApp(`#/lgu/cases/proposal/${proposalId}`);
    cy.get('#main').should('contain.text', proposalTitle).and('contain.text', 'Sector endorsements');
    cy.get('form[data-form="decide"]').within(() => {
      cy.get('input[name="to"][value="under_review"]').check();
      cy.get('textarea[name="justification"]').type('Forwarded to the Engineering Office for costing.');
      cy.root().submit();
    });
    cy.shouldToast('Decision recorded');
  });

  it('citizen sees the decision and justification on their request', () => {
    cy.loginAs('citizen');
    cy.visitApp(`#/requests/${requestId}`);
    cy.get('#main').should('contain.text', justification);
    cy.get('#main').invoke('text').should('match', /in progress/i);
  });

  it('a new resident can register and lands on the Community Hub', () => {
    cy.visit('/#/register');
    cy.get('form[data-form="register"]').within(() => {
      cy.get('input[name="full_name"]').type(`Cypress Resident ${run}`);
      cy.get('input[name="email"]').type(`cypress.${run}@example.com`);
      cy.get('input[name="contact_number"]').type('09171234567');
      cy.get('input[name="barangay"]').type('Poblacion');
      cy.get('input[name="password"]').type('password123');
      cy.get('input[name="confirm"]').type('password123');
      cy.get('input[name="agree"]').check();
      cy.root().submit();
    });
    cy.location('hash', { timeout: 15000 }).should('eq', '#/feed');
    cy.title().should('eq', 'Community Hub · CivicLink');
    cy.get('.terms-wrap').should('not.exist'); // terms were accepted during registration
  });
});
