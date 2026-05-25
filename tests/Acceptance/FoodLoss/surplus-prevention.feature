Feature:
    I want to make sure that the system allows to edit the prevention assessment of a surplus

    Scenario: As an owner, I can access the prevention assessment step
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/prevention"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, I can save and continue to transformation step
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/prevention"
        When I submit a form with "canBePrevented:1" and button "Continuar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Prevención actualizada con éxito"

    Scenario: As an owner, I can save and return to the surplus list
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/prevention"
        When I submit a form with "canBePrevented:0" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Prevención actualizada con éxito"

    Scenario: As an owner, I can't access the prevention assessment of a surplus from another account
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/prevention"
        Then the response status should be 403
