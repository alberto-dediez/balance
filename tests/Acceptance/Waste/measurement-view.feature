Feature:
    I want to make sure that the system is allow to access the details of a measurement

    Scenario: As an user, I can access to view my own measurements by API
        Given account "MyCompany" with id "mycompany" exists
        Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
        Given waste "Plastico" with id "plastico" exists
        Given property "Hotel" with id "hotel" of account "mycompany" exists
        Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
        Given container for "plastico" in "hotel" and managed by "sadeco" with id "container" exists
        Given measurement for container "container" and account "mycompany" from user "user" with id "111" exists
        When I am authenticated in API as "user@dediez.software"
        And I send a GET request to "/api/es/measurements/111"
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "measurement_id": "8de87304-f72e-4a62-bca9-313b27d068f3",
            "property": "Hotel",
            "waste": "Plastico",
            "waste_manager": "Sadeco",
            "quantity": 3,
            "load": "35",
            "unit": "%",
            "purpose": "Desecho",
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
        When I send a GET request to "/api/es/measurements/111"
        Then the status code should be 403
