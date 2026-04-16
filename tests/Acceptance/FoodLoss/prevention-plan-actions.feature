Feature:
  I want to make sure that the system allows managing proposed actions of a prevention plan

  Scenario: As an admin, I can't access the actions step
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given prevention plan "Plan 1" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/prevention-plans/edit/333/actions"
    Then the response status should be 403

  Scenario: As an owner, I can access the actions step
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/prevention-plans/edit/333/actions"
    Then the response status should be 200

  Scenario: As an owner, I cannot access the actions of a plan from another account
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given prevention plan "Plan 1" with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/prevention-plans/edit/333/actions"
    Then the response status should be 403

  Scenario: As a normal user I can't access the actions step
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/prevention-plans/edit/333/actions"
    Then the response status should be 403
