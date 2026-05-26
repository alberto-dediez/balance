Feature:
    I want to make sure that the system allows to edit the composting assessment of a surplus

    Scenario: As an owner, I can access the composting assessment step
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/composting"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, answering yes finishes the process and redirects to the surplus list
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/composting"
        When I submit a form with "canBeComposted:1" and button "Continuar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha registrado un nuevo excedente con destino a compostaje o consumo no alimentario"

    Scenario: As an owner, answering no continues to the elimination step
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/composting"
        When I submit a form with "canBeComposted:0" and button "Continuar"
        When I follow redirect
        Then the response status should be 200

    Scenario: As an owner, I can save and return to the surplus list
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/composting"
        When I submit a form with "canBeComposted:0" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200

    Scenario: As an owner, I can't access the composting assessment of a surplus from another account
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1/composting"
        Then the response status should be 403
