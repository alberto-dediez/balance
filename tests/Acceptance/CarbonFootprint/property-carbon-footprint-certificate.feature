Feature:
    I want to issue a verifiable footprint certificate per stay for a completed property carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel Los Olivos" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists

    Scenario: Issuing the certificate from the results step generates its PDF
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then I see a text "Certificado de huella"
        Then I see a text "Emitir certificado"
        When I issue the certificate of property carbon footprint "999" with "41000" stays
        Then the certificate of property carbon footprint "999" should be "pending"
        When the queued messages are processed
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "Certificado de huella emitido. El PDF estará disponible en unos minutos."
        Then the certificate of property carbon footprint "999" should be "generated"
        Then the certificate of property carbon footprint "999" should have "4.50" kg per stay
        When I download the certificate of property carbon footprint "999"
        Then the response status should be 200
        Then the response header "Content-Type" should be "application/pdf"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then I see a text "Descargar certificado"
        Then I see a text "Descargar PDF"

    Scenario: Without a generated report the certificate cannot be issued
        Given property carbon footprint "999" is completed
        Given "property" carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then I see a text "El certificado de huella se puede emitir cuando el informe esté generado."
        Then I do not see a text "Emitir certificado"
        When I issue the certificate of property carbon footprint "999" with "41000" stays
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "La huella debe estar completada y su informe generado para emitir el certificado."
        Then property carbon footprint "999" should have no certificate

    Scenario: Stays above the storable limit are rejected
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I issue the certificate of property carbon footprint "999" with "2147483648" stays
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "Este valor debería ser menor o igual que 2147483647."
        Then I do not see a text "Certificado de huella emitido. El PDF estará disponible en unos minutos."
        Then property carbon footprint "999" should have no certificate

    Scenario: A footprint that is not completed cannot be certified
        When I am logged as "owner@dediez.software"
        When I issue the certificate of property carbon footprint "999" with "41000" stays
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "La huella debe estar completada y su informe generado para emitir el certificado."
        Then property carbon footprint "999" should have no certificate

    Scenario: A footprint with a current certificate cannot be certified again
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        Given property carbon footprint "999" has a generated certificate with code "HUEL-2345-ABCD" and token "abcdefghijklmnopqrstuvwxyz012345" and "41000" stays
        When I am logged as "owner@dediez.software"
        When I issue the certificate of property carbon footprint "999" with "42000" stays
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "La huella ya tiene un certificado vigente."
        Then the certificate of property carbon footprint "999" should be "generated"
        When I go to the verification page of the certificate of property carbon footprint "999"
        Then I see a text "Certificado válido"
        Then I see a text "HUEL-2345-ABCD"

    Scenario: Anybody verifies the certificate without signing in
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given property carbon footprint "999" has a generated certificate with code "HUEL-2345-ABCD" and token "abcdefghijklmnopqrstuvwxyz012345" and "41000" stays
        When I go to the verification page of the certificate of property carbon footprint "999"
        Then the response status should be 200
        Then I see a text "Certificado válido"
        Then I see a text "HUEL-2345-ABCD"
        Then I see a text "Hotel Los Olivos"
        Then I see a text "2025"
        Then I see a text "4,50"
        When I go to "/es/footprint-certificate/unknownunknownunknownunknown"
        Then the response status should be 404

    Scenario: Reopening the footprint revokes its certificate
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        Given property carbon footprint "999" has a generated certificate with code "HUEL-2345-ABCD" and token "abcdefghijklmnopqrstuvwxyz012345" and "41000" stays
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then I see a text "Descargar certificado"
        When I submit a form with "" and button "Reabrir cálculo"
        Then I am redirected to route "property_carbon_footprints_scope_1"
        Then the certificate of property carbon footprint "999" should be "revoked"
        When I go to "/es/footprint-certificate/abcdefghijklmnopqrstuvwxyz012345"
        Then the response status should be 200
        Then I see a text "Certificado revocado"
        Then I see a text "Fecha de revocación"
        When I download the certificate of property carbon footprint "999"
        Then the response status should be 404
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then I do not see a text "Descargar certificado"

    Scenario: Completing the footprint again allows issuing a new certificate
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        Given property carbon footprint "999" has a generated certificate with code "HUEL-2345-ABCD" and token "abcdefghijklmnopqrstuvwxyz012345" and "41000" stays
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        When I submit a form with "" and button "Reabrir cálculo"
        Then the certificate of property carbon footprint "999" should be "revoked"
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I issue the certificate of property carbon footprint "999" with "36900" stays
        When the queued messages are processed
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "Certificado de huella emitido. El PDF estará disponible en unos minutos."
        Then the certificate of property carbon footprint "999" should be "generated"
        Then the certificate of property carbon footprint "999" should have "5.00" kg per stay
        When I go to the verification page of the certificate of property carbon footprint "999"
        Then I see a text "Certificado válido"
        Then I do not see a text "HUEL-2345-ABCD"
        When I go to "/es/footprint-certificate/abcdefghijklmnopqrstuvwxyz012345"
        Then I see a text "Certificado revocado"

    Scenario: An owner of another account can neither issue nor download the certificate
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        Given property carbon footprint "999" has a generated certificate with code "HUEL-2345-ABCD" and token "abcdefghijklmnopqrstuvwxyz012345" and "41000" stays
        When I am logged as "other@dediez.software"
        When I issue the certificate of property carbon footprint "999" with "41000" stays
        Then the response status should be 403
        When I download the certificate of property carbon footprint "999"
        Then the response status should be 403

    Scenario: An admin impersonating the account issues the certificate on its behalf
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given "property" carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "admin@dediez.software"
        When I impersonate account "MyCompany"
        When I issue the certificate of property carbon footprint "999" with "41000" stays
        Then I am redirected to route "property_carbon_footprints_results"
        Then the certificate of property carbon footprint "999" should be "pending"

    Scenario: Removing the footprint removes its certificates
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        Given property carbon footprint "999" has a generated certificate with code "HUEL-2345-ABCD" and token "abcdefghijklmnopqrstuvwxyz012345" and "41000" stays
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/remove" with csrf token "remove"
        Then the response status should be 302
        Then property carbon footprint "999" should have no certificate

    Scenario: The footprint list offers the certificate download only once it is generated
        Given property carbon footprint "999" is completed with "184500" kg CO₂eq
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints"
        Then the response status should be 200
        Then I do not see a text "Descargar certificado de huella"
        Given property carbon footprint "999" has a generated certificate with code "HUEL-2345-ABCD" and token "abcdefghijklmnopqrstuvwxyz012345" and "41000" stays
        When I go to "/es/property-carbon-footprints"
        Then I see a text "Descargar certificado de huella"
        Then I see a link to "/es/property-carbon-footprint-certificates/999/download"
