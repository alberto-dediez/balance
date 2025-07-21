Feature:
  I want to make sure that the system is allow to access the details of a container

  Scenario: As an user, I can access to view my own containers by API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
    Given container for "plastico" in "hotel" and managed by "sadeco" with id "container" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a GET request to "/api/containers/container"
    Then the status code should be 200
    And the response JSON should be similar to:
    """
    {
      "container_id": "8de87304-f72e-4a62-bca9-313b27d068f3",
      "property": "Hotel",
      "waste": "Plastico",
      "waste_manager": "Sadeco",
      "unit": "%",
      "purpose": "Deshecho"
    }
    """

  Scenario: Not authenticated user
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
    Given container for "plastico" in "hotel" and managed by "sadeco" with id "container" exists
    When I send a GET request to "/api/containers/container"
    Then the status code should be 403