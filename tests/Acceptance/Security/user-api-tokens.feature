Feature:
    I want to make sure that administrators and owners can manage the api tokens of other users

    Scenario: As an admin, I can list the api tokens of any user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" with id "user-1" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_admin_list_0000000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/edit/user-1"
        Then I see a text "Tokens de API"
        When I go to "/es/admin/users/user-1/api-tokens"
        Then the response status should be 200
        Then I see a text "Integration"
        Then I see a text "hyd_admin_li"
        Then I do not see a text "hyd_admin_list_0000000000000000000000000000000"

    Scenario: As an admin, I can revoke the api token of any user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" with id "user-1" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_admin_revoke_00000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/user-1/api-tokens/token-1/revoke" with csrf token "revoke"
        Then I am redirected to "/es/admin/users/user-1/api-tokens"
        Then I see a text "Token revocado"
        Then user "user@dediez.software" should have 0 active api tokens

    Scenario: As an admin, I can not revoke a token through another user
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" with id "user-1" of account "333" exists
        Given user "other@dediez.software" with role "ROLE_USER" with id "user-2" of account "333" exists
        Given user "other@dediez.software" has an api token "hyd_admin_other_000000000000000000000000000000" named "Integration" with id "token-2"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/user-1/api-tokens/token-2/revoke" with csrf token "revoke"
        Then the response status should be 404
        Then user "other@dediez.software" should have 1 active api tokens

    Scenario: As an admin, I can not manage the api tokens of a super admin
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "333" allows api tokens
        Given user "super@dediez.software" with role "ROLE_SUPER_ADMIN" with id "super-1" of account "333" exists
        Given user "super@dediez.software" has an api token "hyd_admin_super_000000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/users/super-1/api-tokens"
        Then the response status should be 403
        When I go to "/es/admin/users/super-1/api-tokens/token-1/revoke" with csrf token "revoke"
        Then the response status should be 403
        Then user "super@dediez.software" should have 1 active api tokens

    Scenario: As an owner, I can list and revoke the api tokens of the users of my account
        Given account "MyCompany" with id "333" allows api tokens
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "333" exists
        Given user "user@dediez.software" with role "ROLE_USER" with id "user-1" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_owner_revoke_00000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "owner@dediez.software"
        When I go to "/es/users/user-1/api-tokens"
        Then the response status should be 200
        Then I see a text "Integration"
        When I go to "/es/users/user-1/api-tokens/token-1/revoke" with csrf token "revoke"
        Then I am redirected to "/es/users/user-1/api-tokens"
        Then I see a text "Token revocado"
        Then user "user@dediez.software" should have 0 active api tokens

    Scenario: As an owner of another account, I can not manage the api tokens of the user
        Given account "MyCompany" with id "333" allows api tokens
        Given account "OtherCompany" with id "444" allows api tokens
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "444" exists
        Given user "user@dediez.software" with role "ROLE_USER" with id "user-1" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_other_owner_000000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "owner@dediez.software"
        When I go to "/es/users/user-1/api-tokens"
        Then the response status should be 403
        When I go to "/es/users/user-1/api-tokens/token-1/revoke" with csrf token "revoke"
        Then the response status should be 403
        Then user "user@dediez.software" should have 1 active api tokens

    Scenario: As a user, I can not manage the api tokens of another user
        Given account "MyCompany" with id "333" allows api tokens
        Given user "plain@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" with role "ROLE_USER" with id "user-1" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_plain_user_0000000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "plain@dediez.software"
        When I go to "/es/users/user-1/api-tokens"
        Then the response status should be 403
        When I go to "/es/admin/users/user-1/api-tokens"
        Then the response status should be 403
        When I go to "/es/users/user-1/api-tokens/token-1/revoke" with csrf token "revoke"
        Then the response status should be 403
        Then user "user@dediez.software" should have 1 active api tokens
