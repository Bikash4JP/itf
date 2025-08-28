import pandas as pd

# CSV read karo
df = pd.read_csv('talents.csv', encoding='utf-8')

# Columns jisme ¥ hai
money_columns = ['管理費', '紹介料', '支援者の家賃', '共益費', '紹介手数料']

# ¥ aur commas hatao
for col in money_columns:
    df[col] = df[col].replace({r'¥': '', r',': ''}, regex=True).astype(float)

# Cleaned CSV save karo
df.to_csv('cleaned_correct.csv', index=False, encoding='utf-8')