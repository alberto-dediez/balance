Feature:
    Ability to view the history of a user in web

    Scenario: As admin I can access the history of an existing user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with id "aaaaaaaa-0000-0000-0000-000000000001" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/aaaaaaaa-0000-0000-0000-000000000001/history"
        Then the response status should be 200

    Scenario: As normal user I can't access the history of another user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with id "aaaaaaaa-0000-0000-0000-000000000001" exists
        Given user "user2@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user2@dediez.software"
        When I go to "/es/admin/users/aaaaaaaa-0000-0000-0000-000000000001/history"
        Then the response status should be 403

    Scenario: Viewing history of a non-existent user returns 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/00000000-0000-0000-0000-000000000000/history"
        Then the response status should be 404
