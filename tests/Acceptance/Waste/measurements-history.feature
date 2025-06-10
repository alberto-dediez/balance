Feature:
    Access to measurement history of the logged user

    Scenario: Ask api about measurement history
        Given account "MyCompany" with id "mycompany" exists
        Given user "user@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am authenticated in API as "user@dediez.software"
        When I send a GET request to "/api/measurements/history"
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "username": "user@dediez.software",
            "measurements": [
                {
                    "property": "Hotel Abaco",
                    "waste": "Material Nuclear",
                    "waste_manager": "Sadeco",
                    "containers": 3,
                    "load": "50 %",
                    "purpose": "Deshecho",
                    "registered_at": "2025-06-10 15:12:04"
                }
            ]
        }
        """

    Scenario: Not authenticated user
        When I send a GET request to "/api/measurements/history"
        Then the status code should be 403