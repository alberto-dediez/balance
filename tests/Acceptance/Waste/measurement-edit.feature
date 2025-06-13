Feature:
  I want to make sure that the system is allow to access the details of a measurement

  Scenario: As an owner, I can access to edit measurements of my account
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "plastico" in "hotel" with id "container" exists
    Given measurement for container "container" and account "mycompany" from user "user1" with id "111" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/measurements/edit/111"
    Then the response status should be 200

  Scenario: As an owner, I can't access to edit measurements of other accounts
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "other" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "other" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "other" exists
    Given container for "plastico" in "hotel" with id "container" exists
    Given measurement for container "container" and account "other" from user "user1" with id "111" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/measurements/edit/111"
    Then the response status should be 403

  Scenario: As an user, I can access to edit my own measurements
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "plastico" in "hotel" with id "container" exists
    Given measurement for container "container" and account "mycompany" from user "user1" with id "111" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/measurements/edit/111"
    Then the response status should be 200

  Scenario: As an user, I can't access to edit measurements of other users of my account
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given user "user2@dediez.software" with role "ROLE_USER" with id "user2" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "plastico" in "hotel" with id "container" exists
    Given measurement for container "container" and account "mycompany" from user "user1" with id "111" exists
    Given measurement for container "container" and account "mycompany" from user "user2" with id "222" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/measurements/edit/222"
    Then the response status should be 403

  Scenario: As an user, I can access to edit my own measurements by API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
    Given container for "plastico" in "hotel" and managed by "sadeco" with id "container" exists
    Given measurement for container "container" and account "mycompany" from user "user" with id "111" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a POST request to "/api/measurements/111" with body:
    """
    {
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
    When I send a POST request to "/api/measurements/111" with body:
    """
    {
      "quantity": 3,
      "load": "35",
      "justification": "Ninguna"
    }
    """
    Then the status code should be 403