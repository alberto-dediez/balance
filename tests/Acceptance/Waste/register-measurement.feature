Feature:
  I want to make sure that the software allow to register measurements

  Scenario: As an user, I can access to register new measurements
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    When I am logged as "user@dediez.software"
    When I go to "/es/measurements/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As an user, I can register new measurements
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "pastico" in "hotel" with id "container" exists
    When I am logged as "user@dediez.software"
    When I go to "/es/measurements/register"
    Then the response status should be 200
    When I submit a form with "quantity:1|load:12.5|containerId:container" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Medición registrada correctamente"