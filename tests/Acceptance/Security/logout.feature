Feature:
    I want to make sure that the logout link is protected against CSRF

    Scenario: A logged user can log out following the link with its token
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/logout" with csrf token "logout" as "_csrf_token"
        Then the response status should be 302

    Scenario: Without a CSRF token the logout is refused
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/logout"
        Then the response status should be 403
