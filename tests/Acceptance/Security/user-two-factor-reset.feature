Feature:
    Administrators reset the second factor of a user

    Scenario: An admin resets the second factor of a user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        And user "user@dediez.software" with id "2fa-user" and two factor method "Totp" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/edit/2fa-user"
        Then the response status should be 200
        Then I see a text "Segundo factor"
        Then I see a text "Aplicación de autenticación"
        Then I see a link with name "Restablecer segundo factor"
        When I go to "/es/admin/users/2fa-user/two-factor/reset" with csrf token "two-factor-reset"
        Then I am redirected to "/es/admin/users/edit/2fa-user"
        Then user "user@dediez.software" has no two factor method

    Scenario: An owner resets the second factor of a user of the account
        Given account "MyCompany" with id "1111" exists
        And user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        And user "user@dediez.software" with id "2fa-user" of account "1111" with two factor method "Email" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/users/2fa-user/two-factor/reset" with csrf token "two-factor-reset"
        Then the response status should be 302
        Then user "user@dediez.software" has no two factor method

    Scenario: An owner cannot reset the second factor of a user of another account
        Given account "MyCompany" with id "1111" exists
        And account "OtherCompany" with id "2222" exists
        And user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        And user "user@dediez.software" with id "2fa-user" of account "2222" with two factor method "Email" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/users/2fa-user/two-factor/reset" with csrf token "two-factor-reset"
        Then the response status should be 403
        Then user "user@dediez.software" has two factor method "Email"

    Scenario: Resetting the second factor requires the csrf token
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        And user "user@dediez.software" with id "2fa-user" and two factor method "Email" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/2fa-user/two-factor/reset"
        Then the response status should be 403
        Then user "user@dediez.software" has two factor method "Email"
