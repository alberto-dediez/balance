Feature:
  I want to make sure that the system is allow to remove a waste manager

  Scenario: As normal user I can't remove waste managers
    Given account "MyCompany" with id "my-company" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "my-company" exists
    Given waste manager "Hotel" with id "sadeco" of account "my-company" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/waste-managers/remove/sadeco"
    Then the response status should be 403

  Scenario: As an owner, I can access to remove waste managers of my account
    Given account "MyCompany" with id "my-company" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-company" exists
    Given waste manager "Hotel" with id "sadeco" of account "my-company" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/waste-managers/remove/sadeco"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado el gestor de residuos"

  Scenario: As an owner, I can't access to remove waste managers of other accounts
    Given account "MyCompany" with id "my-company" exists
    Given account "OtherCompany" with id "111" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-company" exists
    Given waste manager "Hotel" with id "sadeco" of account "111" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/waste-managers/remove/sadeco"
    Then the response status should be 403