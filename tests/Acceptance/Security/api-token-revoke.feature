Feature:
    I want to make sure that users can revoke their api tokens

    Scenario: As a user, I can revoke my token
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_revoke_mine_0000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens/token-1/revoke" with csrf token "revoke"
        Then I am redirected to "/es/profile/api-tokens"
        Then I see a text "Token revocado"
        Then user "user@dediez.software" should have 0 active api tokens

    Scenario: As a user, I can not revoke my token without a csrf token
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_revoke_mine_0000000000000000000000000000" named "Integration" with id "token-1"
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens/token-1/revoke"
        Then the response status should be 403
        Then user "user@dediez.software" should have 1 active api tokens

    Scenario: As a user, I can not revoke the token of another user
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "other@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "other@dediez.software" has an api token "hyd_revoke_other_000000000000000000000000000" named "Integration" with id "token-2"
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens/token-2/revoke" with csrf token "revoke"
        Then the response status should be 403
        Then user "other@dediez.software" should have 1 active api tokens
