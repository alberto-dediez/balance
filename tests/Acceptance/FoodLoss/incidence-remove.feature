Feature:
  I want to make sure that the system is allow to remove an incidence

  Scenario: As an owner, I can access to remove my incidences
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given incidence "Item 1" with id "333" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/incidences/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado la incidencia Item 1"

  Scenario: As an owner, I can't remove incidences from other account
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given incidence "Item 1" with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/incidences/remove/333"
    Then the response status should be 403
