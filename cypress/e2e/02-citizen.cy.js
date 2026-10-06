// Read-only citizen checks: these open and validate forms but never save anything.
describe('Citizen portal', () => {
  beforeEach(() => cy.loginAs('citizen'));

  it('loads the Community Hub with the three civic actions', () => {
    cy.visitApp('#/feed');
    cy.shouldShowPage('Community Hub');
    cy.get('.hh-actions').within(() => {
      cy.contains('Request a service');
      cy.contains('Raise a concern');
      cy.contains('Propose an initiative');
    });
  });

  [
    ['#/proposals', 'Community Proposals'],
    ['#/consultations', 'Public Consultations'],
    ['#/requests', 'Service Requests'],
    ['#/complaints', 'Concerns & Complaints'],
    ['#/feedback', 'Feedback & Ratings'],
    ['#/notifications', 'Notifications'],
    ['#/profile', 'My Civic Profile'],
    ['#/legal', 'Laws & Terms'],
  ].forEach(([hash, title]) => {
    it(`opens ${title} (${hash})`, () => {
      cy.visitApp(hash);
      cy.shouldShowPage(title);
    });
  });

  it('opens the composer and closes it with Cancel', () => {
    cy.visitApp('#/feed');
    cy.openComposer('request');
    cy.get('#modal').contains('button', 'Cancel').click();
    cy.get('#modal').should('not.exist');
  });

  it('validates the proposal form before saving', () => {
    cy.visitApp('#/feed');
    cy.openComposer('proposal');
    cy.get('#modal form[data-form="new-proposal"]').within(() => {
      cy.get('select[name="category"] option').should('have.length', 8);
      cy.get('input[name="title"]').type('Short');
      cy.get('select[name="category"]').select('education');
      cy.get('textarea[name="description"]').type('A description long enough to pass.');
      cy.get('textarea[name="justification"]').type('Needed by the community.');
      cy.get('textarea[name="expected_benefits"]').type('Better services.');
      cy.get('input[name="legal_ok"]').check();
      cy.contains('button[type="submit"]', 'Publish proposal').click();
      cy.get('.form-err').should('be.visible').and('contain.text', 'Title should be at least 8 characters');
    });
  });

  it('routes each service type to the right department', () => {
    cy.visitApp('#/feed');
    cy.openComposer('request');
    [
      ['Streetlight repair', 'Engineering Office'],
      ['Garbage collection', 'City Environment Office'],
      ['Stray animal control', 'City Health Office'],
      ['Social welfare assistance', 'Social Welfare Office'],
      ['Traffic signage / signals', 'Traffic Management Office'],
      ['Barangay certificate / documents', 'Barangay Affairs Office'],
    ].forEach(([service, dept]) => {
      cy.get('select[name="service_type"]').select(service);
      cy.get('#svc-dept').should('contain.text', `Will be routed to ${dept}`);
    });
  });

  it('validates the service request description', () => {
    cy.visitApp('#/feed');
    cy.openComposer('request');
    cy.get('#modal form[data-form="new-request"]').within(() => {
      cy.get('select[name="service_type"]').select('Pothole / road repair');
      cy.get('select[name="priority"]').should('have.value', 'normal').select('high');
      cy.get('textarea[name="description"]').type('Too short');
      cy.get('input[name="legal_ok"]').check();
      cy.contains('button[type="submit"]', 'Submit request').click();
      cy.get('.form-err').should('contain.text', 'at least 15 characters');
    });
  });

  it('validates the complaint details', () => {
    cy.visitApp('#/feed');
    cy.openComposer('complaint');
    cy.get('#modal form[data-form="new-complaint"]').within(() => {
      cy.get('input[name="subject"]').type('Rude front desk');
      cy.get('select[name="category"]').select('personnel');
      cy.get('textarea[name="details"]').type('Too short.');
      cy.get('input[name="legal_ok"]').check();
      cy.contains('button[type="submit"]', 'File complaint').click();
      cy.get('.form-err').should('contain.text', 'at least 20 characters');
    });
  });

  it('requires the legal confirmation checkbox on submissions', () => {
    cy.visitApp('#/feed');
    cy.openComposer('complaint');
    cy.get('#modal input[name="legal_ok"]').should('have.attr', 'required');
  });

  it('applies accessibility settings and resets them', () => {
    cy.visitApp('#/feed');
    cy.get('.topnav [data-act="a11y"]').click();
    cy.get('#modal input[data-a11y="contrast"]').check({ force: true });
    cy.get('html').should('have.attr', 'data-contrast', 'high');
    cy.get('#modal input[name="a11y-text"][value="xl"]').check({ force: true });
    cy.get('html').should('have.attr', 'data-text', 'xl');
    cy.get('#modal').contains('button', 'Reset to default').click();
    cy.get('html').should('have.attr', 'data-contrast', '').and('have.attr', 'data-text', '');
  });
});
