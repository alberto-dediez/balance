Feature:
    Access to api documentation

    Scenario: Api Documentation is accessible
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/openapi/documentation"
        Then the response status should be 200

    Scenario: As normal user I can't access documentation
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/openapi/documentation"
        Then the response status should be 403

    Scenario: As anonym user I can't access documentation
        When I go to "/openapi/documentation"
        Then the response status should be 403