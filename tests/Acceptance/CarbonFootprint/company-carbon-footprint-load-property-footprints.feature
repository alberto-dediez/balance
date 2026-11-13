Feature:
    Ability to load the activities of the completed property footprints into a company carbon footprint scope

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given combustible "Gas natural" with id "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" and emission factor for year "2025" exists
        Given combustible "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" has an emission factor of "1.5" per "l" for year "2025"

    Scenario: The completed property footprint is copied and the other properties are kept
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property "Hotel B" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given property carbon footprint with id "PF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PF1" has a combustible "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" activity of "150" kg
        Given property carbon footprint "PF1" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar desde huellas de propiedad"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then I see a text "1 propiedad"
        Then I see a text "Propiedades omitidas (1): Hotel B: Sin huella completada del año"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "2222"
        Then property carbon footprint "PF1" should have 1 activities of source "fixed_facilities" in scope "scope_1"

    Scenario: A property footprint that is not completed is not copied
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property carbon footprint with id "PF1" for property "1111" of account "666" and year "2025" with scope 2 answered exists
        Given property carbon footprint "PF1" has a combustible "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" activity of "150" kg
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar desde huellas de propiedad"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then I see a text "Ninguna propiedad de la empresa tiene una huella completada con actividades en este alcance"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "1111"

    Scenario: A precalculated property footprint is not copied
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property carbon footprint with id "PF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PF1" is precalculated
        Given property carbon footprint "PF1" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar desde huellas de propiedad"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then I see a text "Propiedades omitidas (1): Hotel A: Huella ya calculada"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "1111"

    Scenario: A difference with the current emission factors is reported
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property carbon footprint with id "PF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PF1" has a combustible "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" activity of "1" kg
        Given property carbon footprint "PF1" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar desde huellas de propiedad"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then I see a text "difieren de las de la huella de propiedad: Hotel A: 1,00 →"
        Then company carbon footprint "999" should have 1 activities of source "fixed_facilities" in scope "scope_1" for property "1111"

    Scenario: The action is not shown in a precalculated company footprint
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" precalculated exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        Then I do not see a text "Cargar desde huellas de propiedad"

    Scenario: A franchised property of the company does not receive activities
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property "Hotel Franquicia" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given property "2222" has operating regime "franchised"
        Given property carbon footprint with id "PF2" for property "2222" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PF2" has a combustible "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" activity of "150" kg
        Given property carbon footprint "PF2" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-1"
        When I click on link "Cargar desde huellas de propiedad"
        Then I am redirected to route "company_carbon_footprints_scope_1"
        Then I see a text "Propiedades omitidas (1): Hotel A: Sin huella completada del año"
        Then I do not see a text "Hotel Franquicia: Sin huella completada del año"
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "2222"

    Scenario: The property footprints are copied into scope 2
        Given electricity marketer "Comercializadora" with id "bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb" and emission factor for year "2025" exists
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property carbon footprint with id "PF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PF1" has an electricity marketer "bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb" activity of "150" kg
        Given property carbon footprint "PF1" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 1 answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-2"
        When I click on link "Cargar desde huellas de propiedad"
        Then I am redirected to route "company_carbon_footprints_scope_2"
        Then I see a text "1 propiedad"
        Then company carbon footprint "999" should have 1 activities of source "building_electricity" in scope "scope_2" for property "1111"
        Then property carbon footprint "PF1" should have 1 activities of source "building_electricity" in scope "scope_2"

    Scenario: An owner of another account is denied
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property carbon footprint with id "PF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PF1" has a combustible "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" activity of "150" kg
        Given property carbon footprint "PF1" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope_1/property-footprints" with csrf token "property_footprints_load_999"
        Then the response status should be 403
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "1111"

    Scenario: An invalid csrf token is denied
        Given property "Hotel A" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given property carbon footprint with id "PF1" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "PF1" has a combustible "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa" activity of "150" kg
        Given property carbon footprint "PF1" is completed
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope_1/property-footprints?_token=invalid"
        Then the response status should be 403
        Then company carbon footprint "999" should have 0 activities of source "fixed_facilities" in scope "scope_1" for property "1111"
