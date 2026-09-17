Feature:
    Ability to map a Bioscore catalog item to a platform master from its edit screen

    Scenario: As admin I reach the edit screen from the list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gasóleo C" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        When I click on the action "Editar"
        Then the response status should be 200
        Then I see a text "Gasóleo C"
        Then I see a form field "targetId"
        Then I see a form field "ignore"

    Scenario: As admin I map an item to a combustible
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given combustible "Gas natural" with id "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gasóleo C" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/catalog-items/edit/11111111-1111-1111-1111-111111111111"
        When I submit a form with "targetId:aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" and button "Guardar y salir"
        Then I am redirected to route "admin_bioscore_facility_fuels"
        Then I see a text "Equivalencia de Bioscore actualizada con éxito"
        Then Bioscore catalog item "11111111-1111-1111-1111-111111111111" should be mapped to "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa"

    Scenario: As admin I ignore an item
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gasóleo C" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/catalog-items/edit/11111111-1111-1111-1111-111111111111"
        When I submit a form with "ignore:1" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Equivalencia de Bioscore actualizada con éxito"
        Then Bioscore catalog item "11111111-1111-1111-1111-111111111111" should be ignored

    Scenario: As admin I unmap an item by clearing its target
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given combustible "Gas natural" with id "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gasóleo C" with id "11111111-1111-1111-1111-111111111111" exists
        Given Bioscore catalog item "11111111-1111-1111-1111-111111111111" is mapped to "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/catalog-items/edit/11111111-1111-1111-1111-111111111111"
        When I submit a form with "targetId:" and button "Guardar y salir"
        When I follow redirect
        Then Bioscore catalog item "11111111-1111-1111-1111-111111111111" should be unmapped

    Scenario: An unknown item answers 404
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/catalog-items/edit/99999999-9999-9999-9999-999999999999"
        Then the response status should be 404

    Scenario: As owner I can't edit a Bioscore catalog item
        Given user "owner1@dediez.software" with role "ROLE_OWNER" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gasóleo C" with id "11111111-1111-1111-1111-111111111111" exists
        When I am logged as "owner1@dediez.software"
        When I go to "/es/admin/bioscore/catalog-items/edit/11111111-1111-1111-1111-111111111111"
        Then the response status should be 403
