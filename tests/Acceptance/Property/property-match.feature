Feature:
  I want to make sure that the API allows matching external properties with existing ones

  Scenario: As an admin, I can match an external property by name via API
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given property "Hotel Barcelo Renacimiento" with id "111" exists
    When I am authenticated in API as "admin@dediez.software"
    And I send a POST request to "/api/es/properties/match" with body:
    """
    {
        "name": "Hotel Barcelo Renacimiento",
        "source": "hotelbeds",
        "external_id": "12345"
    }
    """
    Then the status code should be 200
    And the response JSON should be similar to:
    """
    {
        "property_id": "111"
    }
    """

  Scenario: As an admin, I get null property_id when no match is found via API
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am authenticated in API as "admin@dediez.software"
    And I send a POST request to "/api/es/properties/match" with body:
    """
    {
        "name": "Unknown Property That Does Not Exist",
        "source": "hotelbeds"
    }
    """
    Then the status code should be 200
    And the response JSON should be equal to:
    """
    {
        "property_id": null
    }
    """

  Scenario: Not authenticated user cannot match a property via API
    When I send a POST request to "/api/es/properties/match" with body:
    """
    {
        "name": "Hotel Test",
        "source": "hotelbeds"
    }
    """
    Then the status code should be 403

  Scenario: A non-admin user cannot match a property via API
    Given user "user@dediez.software" with role "ROLE_USER" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a POST request to "/api/es/properties/match" with body:
    """
    {
        "name": "Hotel Test",
        "source": "hotelbeds"
    }
    """
    Then the status code should be 403
