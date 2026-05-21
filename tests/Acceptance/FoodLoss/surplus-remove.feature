Feature:
    I want to make sure that the system allows to remove a surplus

    Scenario: As an owner, I can remove my surplus
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/remove/surplus-1"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el excedente"

    Scenario: As an admin, I can't remove surpluses
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "MyAccount" with id "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/surpluses/remove/surplus-1"
        Then the response status should be 403
