Feature:
    I want to make sure that the system allows to edit a surplus

    Scenario: As an owner, I can access to edit my surplus
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given service category "Desayuno" with id "00000000-0000-0000-0000-000000000001" exists
        Given critical point "Cocina" with id "00000000-0000-0000-0000-000000000001" of account "my-account" exists
        Given surplus cause "Exceso de producción" with id "00000000-0000-0000-0000-000000000001" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1"
        When I submit a form with "date:2026-05-22|serviceCategoryId:00000000-0000-0000-0000-000000000001|criticalPointId:00000000-0000-0000-0000-000000000001|surplusCauseId:00000000-0000-0000-0000-000000000001|foodType:fish|quantity:3|unit:kg" and button "Continuar"
        When I follow redirect
        Then the response status should be 200

    Scenario: As an owner, I can't edit a surplus from another account
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/edit/surplus-1"
        Then the response status should be 403
