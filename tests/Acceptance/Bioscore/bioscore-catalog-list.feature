Feature:
    Ability to list the Bioscore catalog items in web

    Scenario: As admin I see only the items of the catalog of each screen
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gas natural" with id "11111111-1111-1111-1111-111111111111" exists
        Given Bioscore catalog item "2" of catalog "facility_fuel" named "Propano" with id "22222222-2222-2222-2222-222222222222" exists
        Given Bioscore catalog item "3" of catalog "transport_fuel" named "Gasoil" with id "33333333-3333-3333-3333-333333333333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        Then the response status should be 200
        Then I see a text "Combustibles de instalaciones"
        Then I see a text "Gas natural"
        Then I do not see a text "Gasoil"
        Then I see a table with 4 rows
        Then I see a link with name "Sincronizar ahora"

    Scenario: As admin the other three screens answer
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/transport-fuels"
        Then the response status should be 200
        When I go to "/es/admin/bioscore/refrigerant-gases"
        Then the response status should be 200
        When I go to "/es/admin/bioscore/electricity-marketers"
        Then the response status should be 200

    Scenario: The filter of one screen does not affect the others
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gas natural" with id "11111111-1111-1111-1111-111111111111" exists
        Given Bioscore catalog item "2" of catalog "facility_fuel" named "Propano" with id "22222222-2222-2222-2222-222222222222" exists
        Given Bioscore catalog item "3" of catalog "transport_fuel" named "Gasoil" with id "33333333-3333-3333-3333-333333333333" exists
        Given Bioscore catalog item "4" of catalog "transport_fuel" named "Gasolina" with id "44444444-4444-4444-4444-444444444444" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        When I filter by "name" with "Propano"
        Then I see a table with 3 rows
        When I go to "/es/admin/bioscore/transport-fuels"
        Then I see a table with 4 rows

    Scenario: As admin I ignore several items in bulk
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gas natural" with id "11111111-1111-1111-1111-111111111111" exists
        Given Bioscore catalog item "2" of catalog "facility_fuel" named "Propano" with id "22222222-2222-2222-2222-222222222222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        When I submit a form with "elements:[11111111-1111-1111-1111-111111111111,22222222-2222-2222-2222-222222222222]|action:ignore" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then Bioscore catalog item "11111111-1111-1111-1111-111111111111" should be ignored
        Then Bioscore catalog item "22222222-2222-2222-2222-222222222222" should be ignored

    Scenario: As admin I unmap several items in bulk
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given combustible "Gas natural" with id "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" exists
        Given Bioscore catalog item "1" of catalog "facility_fuel" named "Gas natural" with id "11111111-1111-1111-1111-111111111111" exists
        Given Bioscore catalog item "11111111-1111-1111-1111-111111111111" is mapped to "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        When I submit a form with "elements:[11111111-1111-1111-1111-111111111111]|action:unmap" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then Bioscore catalog item "11111111-1111-1111-1111-111111111111" should be unmapped

    Scenario: As normal user I can't access the Bioscore catalogs
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        Then the response status should be 403

    Scenario: As owner I can't access the Bioscore catalogs
        Given user "owner1@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner1@dediez.software"
        When I go to "/es/admin/bioscore/facility-fuels"
        Then the response status should be 403
