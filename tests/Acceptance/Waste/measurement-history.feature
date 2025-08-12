Feature:
    Access to measurement history of the logged user

    Scenario: Ask api about measurement history
        Given account "MyCompany" with id "mycompany" exists
        Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
        Given waste "Plastico" with id "plastico" exists
        Given property "Hotel" with id "hotel" of account "mycompany" exists
        Given waste manager "Sadeco" with id "sadeco" of account "mycompany" exists
        Given container for "plastico" in "hotel" and managed by "sadeco" with id "container" exists
        Given measurement for container "container" and account "mycompany" from user "user" with id "111" exists
        When I am authenticated in API as "user@dediez.software"
        When I send a GET request to "/api/es/measurements/history"
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "username": "user@dediez.software",
            "measurements": [
                {
                    "measurement_id": "8de87304-f72e-4a62-bca9-313b27d068f3",
                    "property": "Hotel Abaco",
                    "waste": "Material Nuclear",
                    "waste_manager": "Sadeco",
                    "quantity": 3,
                    "load": "50 %",
                    "purpose": "Deshecho",
                    "justification": "",
                    "registered_at": "2025-06-10 15:12:04"
                }
            ]
        }
        """

    Scenario: Not authenticated user
        When I send a GET request to "/api/es/measurements/history"
        Then the status code should be 403