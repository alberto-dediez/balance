Feature:
  I want to make sure that the software allow to register an incidence

  Scenario: As an owner, I can access to register new incidences
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/incidences/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't register new incidences
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/incidences/register"
    Then the response status should be 403

  Scenario: As an owner, I can register new incidences
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "Test Property" with id "prop-1" of account "mycompany" exists
    Given critical point "Test CP" with id "cp-1" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/incidences/register"
    When I submit a form with "name:Test Incidence|incidenceType:machinery_failure|originId:cp-1|description:A desc|effect:An effect|propertyId:prop-1" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Incidencia creada correctamente: Test Incidence"
