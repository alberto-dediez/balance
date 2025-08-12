Feature:
  I want to make sure that the software allow to register measurements

  Scenario: As an user, I can access to register new measurements
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    When I am logged as "user@dediez.software"
    When I go to "/es/measurements/register"
    Then the response status should be 200

  Scenario: As an user, when access to register measurements, i see a list of available containers
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "plastico" in "hotel" with id "container" exists
    When I am logged as "user@dediez.software"
    When I go to "/es/measurements/register"
    Then the response status should be 200
    Then I see a link with name "Seleccionar"

  Scenario: As an user, i can register a new measurement after choosing a container
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "plastico" in "hotel" with id "container" exists
    When I am logged as "user@dediez.software"
    When I go to "/es/measurements/register/container"
    Then the response status should be 200
    When I submit a form with "quantity:1|load:12" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Medición registrada correctamente"

  Scenario: As an user, I can access to register my own measurements by API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
    Given container for "plastico" in "hotel" and managed by "sadeco" with id "container" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a POST request to "/api/es/measurements" with body:
    """
    {
      "container_id": "container",
      "quantity": 3,
      "load": "35",
      "justification": "Ninguna"
    }
    """
    Then the status code should be 200
    And the response JSON should be similar to:
    """
    {
      "measurement_id": "8de87304-f72e-4a62-bca9-313b27d068f3",
      "property": "Hotel",
      "waste": "Plastico",
      "waste_manager": "Sadeco",
      "quantity": 3,
      "load": "35 %",
      "purpose": "Deshecho",
      "justification": "Ninguna",
      "registered_at": "2025-06-10 15:12:04"
    }
    """
    
  Scenario: Not authenticated user
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
    Given container for "plastico" in "hotel" and managed by "sadeco" with id "container" exists
    Given measurement for container "container" and account "mycompany" from user "user" with id "111" exists
    When I send a POST request to "/api/es/measurements" with body:
    """
    {
      "container_id": "container",
      "quantity": 3,
      "load": "35",
      "justification": "Ninguna"
    }
    """
    Then the status code should be 403