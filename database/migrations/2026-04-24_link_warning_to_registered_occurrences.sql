-- Ajusta o vínculo das advertências para usar as ocorrências
-- registradas no formulário "Registrar ocorrência" do módulo RH.

ALTER TABLE advertencias_colaboradores
    DROP CONSTRAINT IF EXISTS advertencias_colaboradores_ocorrencia_id_fkey;

ALTER TABLE advertencias_colaboradores
    ADD CONSTRAINT advertencias_colaboradores_ocorrencia_id_fkey
    FOREIGN KEY (ocorrencia_id)
    REFERENCES ocorrencias_colaboradores(id)
    ON DELETE RESTRICT
    NOT VALID;
