Feature:
    Ability to load the Bioscore consumptions into a company carbon footprint scope

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given combustible "Gas natural" with id "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" and emission factor for year "2025" exists
        Given transport "Camión" with id "bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb" and emission factor for year "2025" exists
        Given refrigerant gas "R-410A" with id "99999999-9999-9999-9999-999999999999" and emission factor for year "2025" exists
        Given Bioscore catalog item "7" of catalog "facility_fuel" named "Gas Natural" with id "BI1" exists
        Given Bioscore catalog item "BI1" is mapped to "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa"

    Scenario: Only the property returned by Bioscore is replaced
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property "Hotel B" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given Bioscore returns for property "1111" in "2025" a "facility_fuel" consumption of "1000" "kg" of item "7" named "Gas Natural"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar consumos de Bioscore"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then I see a text "1 propiedad"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "2222"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "none"

    Scenario: A company without properties is reported
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar consumos de Bioscore"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then I see a text "La empresa no tiene propiedades"
