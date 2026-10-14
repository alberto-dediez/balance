Feature:
        Ability to autocomplete destinations in web

        Scenario: As normal I can access an autocomplete list of all destinations
                Given user "user1@dediez.software" with role "ROLE_USER" exists
                Given country "España" with id "spain" exists
                Given destination "Barcelona" of country "spain" with id "bcn" exists
                Given destination "Barceloneta" of country "spain" with id "bcl" exists
                Given destination "Madrid" of country "spain" with id "mad" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/destinations/autocomplete?query=barce"
                Then the response status should be 200
                And I see a JSON that should be similar to:
                """
                [
                        {
                                "label": "Barcelona (España)",
                                "value": "bcn"
                        },
                        {
                                "label": "Barceloneta (España)",
                                "value": "bcl"
                        }
                ]
                """