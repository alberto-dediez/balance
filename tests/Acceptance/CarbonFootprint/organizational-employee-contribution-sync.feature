Feature:
    I want the answers of the employees to become activities of the organizational carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given employee "Ana" with email "ana@example.com" and id "E1" of account "666" exists
        Given employee "Luis" with email "luis@example.com" and id "E2" of account "666" exists
        Given mobility "Coche" with id "M1" and emission factor for year "2025" exists
        Given mobility "Avión" with id "M2" and emission factor for year "2025" exists

    Scenario: Saving the employees step extrapolates the answers to the total workforce
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "0" nights
        Given organizational employee contribution with id "702" for "property" carbon footprint "999" and employee "E2" of account "666" with token "tok-702" exists
        Given organizational employee contribution "702" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "0" nights
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E1,E2" and total "40"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then property carbon footprint "999" should have 1 employee activities in source "employee_commuting"
        Then property carbon footprint "999" should have 1 employee activities in source "business_travel"
        Then property carbon footprint "999" should have 331200 kg in source "employee_commuting"
        Then property carbon footprint "999" should have 72000 kg in source "business_travel"

    Scenario: An excluded category does not receive employee activities
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "0" nights
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope "scope_3" step form excluding source "employee_commuting" with reason "no_data_available" and comment "Sin datos"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        Then I see a text "los datos de los empleados no se contabilizarán en ella"
        When I submit the employees step form with employees "E1" and total "10"
        Then property carbon footprint "999" should have 0 employee activities in source "employee_commuting"
        Then property carbon footprint "999" should have 1 employee activities in source "business_travel"

    Scenario: Completing the footprint includes the answers received after the employees step
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "property" carbon footprint "999" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution with id "702" for "property" carbon footprint "999" and employee "E2" of account "666" with token "tok-702" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/employees"
        When I submit the employees step form with employees "E1,E2" and total "2"
        Then property carbon footprint "999" should have 0 employee activities in source "employee_commuting"
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "0" nights
        When I go to "/es/property-carbon-footprints/999/step/mitigation-plan"
        When I submit the mitigation plan step form
        Then I am redirected to route "property_carbon_footprints_results"
        Then property carbon footprint "999" should have 1 employee activities in source "employee_commuting"
        Then property carbon footprint "999" should have 16560 kg in source "employee_commuting"

    Scenario: In a company footprint the activities are placed in the block of the workplace
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "998" for company "C1" of account "666" and year "2025" with providers answered exists
        Given organizational employee contribution with id "701" for "company" carbon footprint "998" and employee "E1" of account "666" with token "tok-701" exists
        Given organizational employee contribution "701" has workplace "1111"
        Given organizational employee contribution "701" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "0" nights
        Given organizational employee contribution with id "702" for "company" carbon footprint "998" and employee "E2" of account "666" with token "tok-702" exists
        Given organizational employee contribution "702" is answered with "46" weeks, commute "M1" "12" km "5" days "1" occupants and trip "M2" "1200" km "1" trips "1" occupants "0" nights
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/998/step/employees"
        When I submit the employees step form with employees "E1,E2" and total "2"
        Then company carbon footprint "998" should have 1 employee activities in source "employee_commuting" for property "1111"
        Then company carbon footprint "998" should have 1 employee activities in source "employee_commuting" for property ""
        Then company carbon footprint "998" should have 16560 kg in source "employee_commuting"
