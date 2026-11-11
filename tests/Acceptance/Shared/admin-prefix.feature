Feature:
    The web administration lives under the configurable admin prefix

    Scenario: The administration root redirects to the default locale
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        Then going to "/" redirects to "/es"

    Scenario: An anonymous user entering the administration goes to the login
        Then going to "/es/profile" redirects to "/es/login"

    Scenario: An anonymous user requesting a live component goes to the login
        Then going to "/_components/x" redirects to "/es/login"

    Scenario: Administration paths are resolved with the prefix
        Then the path "/es/profile" resolves to the administration
        Then the path "/" resolves to the administration

    Scenario: Paths without an administration equivalent are used as written
        Then the path "/api/health-check" is used as written
        Then the path "/login/sso/google" is used as written

    @public
    Scenario: In a public scenario paths are used as written
        Then the path "/es/profile" is used as written
        Then the path "/" is used as written

    Scenario: After logging out the user returns to the administration
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/logout" with csrf token "logout" as "_csrf_token"
        Then the response redirects to "/"
