Feature:
    I want to fill in the scope 1 and scope 2 steps of a company carbon footprint

    Scenario: The scope 1 step shows one block per property plus the unassigned one
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given combustible "Gas natural" with id "GN1" and emission factor for year "2025" exists
        Given road transport "Camión" with id "RT1" and emission factor for year "2025" exists
        Given refrigerant gas "R-410A" with id "RG1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I see a text "Sin asignar a propiedad"
        Then I see a text "Hotel"

    Scenario: A combustible introduced in a property block is attributed to that property
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given combustible "Gas natural" with id "GN1" and emission factor for year "2025" exists
        Given road transport "Camión" with id "RT1" and emission factor for year "2025" exists
        Given refrigerant gas "R-410A" with id "RG1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        When I submit the company scope "scope_1" step form with a combustible "GN1" of 100 "kg" for property "1111"
        Then I am redirected to route "company_carbon_footprints_scope_2"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "none"

    Scenario: A combustible introduced in the unassigned block stays unassigned
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        Given combustible "Gas natural" with id "GN1" and emission factor for year "2025" exists
        Given road transport "Camión" with id "RT1" and emission factor for year "2025" exists
        Given refrigerant gas "R-410A" with id "RG1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I submit the company scope "scope_1" step form with a combustible "GN1" of 100 "kg" for property "none"
        Then I am redirected to route "company_carbon_footprints_scope_2"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "none"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "1111"

    Scenario: The results step shows the emissions by property chart
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Totales"
        Then I see a text "Emisiones por alcance"

    Scenario: An owner of another account cannot open the wizard
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        Then the response status should be 403
