Feature:
    Ability to impersonate accounts as admin

    Scenario: As admin I see the account switch selector in the topbar
        Given account "AdminAccount" with id "admin-account-id" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text "Cambiar de cuenta"

    Scenario: As regular user I do not see the account switch selector
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I do not see a text "Cambiar de cuenta"

    Scenario: As admin I can impersonate an account and see the impersonation badge
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "AcmeAccount" with id "acme-account-id" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "AcmeAccount"
        Then the response status should be 200
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text "Volver a mi cuenta"

    Scenario: As admin impersonating an account I see that account users
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "AcmeAccount" with id "acme-account-id" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        Given user "acmeuser@dediez.software" with role "ROLE_USER" of account "acme-account-id" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "AcmeAccount"
        Then the response status should be 200
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I see a text "acmeuser@dediez.software"

    Scenario: As admin I can exit account impersonation
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "AcmeAccount" with id "acme-account-id" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "AcmeAccount"
        Then the response status should be 200
        When I exit account impersonation
        Then the response status should be 200
        When I go to "/es/admin/users"
        Then the response status should be 200
        Then I do not see a text "Volver a mi cuenta"

    Scenario: As owner I cannot impersonate an account
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "AcmeAccount" with id "acme-account-id" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "admin-account-id" exists
        When I am logged as "owner@dediez.software"
        When I impersonate account "AcmeAccount"
        Then the response status should be 403

    Scenario: While user-impersonating the account switch is hidden
        Given account "AdminAccount" with id "admin-account-id" exists
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" of account "admin-account-id" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "superadmin@dediez.software"
        When I go to "/es/?_switch_user=user1@dediez.software"
        When I go to "/es/profile"
        Then the response status should be 200
        Then I do not see a text "Cambiar de cuenta"