Feature:
  I want to make sure that the system is allow to access the details of an incidence

  Scenario: As an owner, I can access to edit my incidences
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "Test Property" with id "prop-1" of account "mycompany" exists
    Given critical point "Test CP" with id "cp-1" of account "mycompany" exists
    Given incidence "Item 1" with id "333" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/incidences/edit/333"
    When I submit a form with "name:A new name" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Incidencia actualizada con éxito"

  Scenario: As an owner, I can't access to edit incidences from other account
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given incidence "An item" with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/incidences/edit/333"
    Then the response status should be 403
