Feature:
    Ability to autocomplete users in web

    Scenario: As normal I can access an autocomplete list of all users
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with id "111" exists
        Given user "user2@dediez.software" with id "222" exists
        Given user "user3@dediez.software" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/autocomplete?query=user"
        Then the response status should be 200
        And I see a JSON that should be equal to:
        """
        [
            {
                "label": "user1@dediez.software",
                "value": "111"
            },
            {
                "label": "user2@dediez.software",
                "value": "222"
            },
            {
                "label": "user3@dediez.software",
                "value": "333"
            }
        ]
        """