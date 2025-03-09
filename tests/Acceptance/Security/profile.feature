Feature:
    Any user can access his profile

    Scenario: Profile is reacheable for logged user
        Given user "admin@dediez.software" with role "ROLE_USER" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200