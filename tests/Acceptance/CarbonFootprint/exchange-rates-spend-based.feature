Feature:
    Ability to calculate emissions from an amount spent in a foreign currency

    Scenario: An activity in US dollars is converted to euros before applying a spend-based factor
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given combustible "Gasto en combustible" with id "GN1" and emission factor for year "2025" exists
        Given combustible "GN1" has an emission factor of "0.5" per "EUR" for year "2025"
        Given exchange rate "USD" at rate "1.25" published on "2026-10-14" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I submit the company scope "scope_1" step form with a combustible "GN1" of 250 "USD" for property "1111"
        Then I am redirected to route "company_carbon_footprints_scope_2"
        Then the activities of source "fixed_facilities" in scope "scope_1" of company carbon footprint "999" for property "1111" should emit "100"
