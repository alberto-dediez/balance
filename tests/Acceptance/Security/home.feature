Feature:
    In order to prove that a home page is provided

    Scenario: Home is unreacheable for not logged user
        When I go to "/es/"
        Then the response status should be 302
        Then I am redirected to "/es/login"

    Scenario: Home is reacheable for logged user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/"
        Then the response status should be 200